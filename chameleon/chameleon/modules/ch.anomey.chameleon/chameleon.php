<?php

require_once 'util.php';
require_once 'log.php';

/**
 * Central registry of all extensions, extension points 
 * and namespaces. Every module owns a reference to this registry
 * to load extensions.
 * 
 * @package ch.anomey.chameleon
 * @author Fabian Vogler
 */
class ExtensionRegistry {

	/**
	 * Vector with all extension points.
	 */
	private $extensionPoints;

	public function __construct() {
		$this->extensionPoints = new Vector();
	}

	/**
	 * Adds an extension point to the registry.
	 * 
	 * @param ExtensionPoint $ep the extension point
	 */
	public function addExtensionPoint(ExtensionPoint $ep) {
		$this->extensionPoints[$ep->getNamespace()] = $ep;
	}

	/**
	 * Returns an extension point with the passed namespace. If the
	 * extension point could not be found it returns <code>null</code.>
	 * 
	 * @param string $ns the namespace of the wanted extension point
	 */
	public function getExtensionPoint($ns) {
		return isset ($this->extensionPoints[$ns]) ? $this->extensionPoints[$ns] : null;
	}

	/**
	 * Returns all extension points.
	 */
	public function getExtensionPoints() {
		return $this->extensionPoints;
	}

	/**
	 * Returns the namespaces of all extension points.
	 */
	public function getNamespaces() {
		return $this->extensionPoints->getKeys();
	}
}

/**
 * The module class which can be overloaded by the modules.
 * It makes the extension point registry avaible to the modules
 * so they can find the defined extensions.
 * 
 * @author Fabian Vogler
 */
class Module {
	
	/**
	 * Reference to chameleon.
	 * 
	 * @var Chameleon
	 */
	private $chameleon;
	
	private $name;

	public function __construct(Chameleon $chameleon, $name) {
		$this->chameleon = $chameleon;
		$this->name = $name;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getLogLevel() {
		return $this->chameleon->getLogLevel();
	}
	
	public function getModule($name) {
		return $this->chameleon->getModule($name);
	}

	/**
	 * This method can be overloaded. It get's called after all 
	 * modules are loaded if the chameleon user want's so.
	 */
	public function invoke() {

	}

	public function getExtensionRegistry() {
		return $this->chameleon->getExtensionRegistry();
	}

	public function getExtensions($ns) {
		return $this->getExtensionRegistry()->getExtensionPoint($ns) != null ? $this->getExtensionRegistry()->getExtensionPoint($ns)->getExtensions() : new Vector();
	}
}

abstract class Extension {
	/**
	 * Default constructor which can be overloaded to read information
	 * from the extension point element.
	 */
	public function __construct(ExtensionPointElement $element) {

	}
}

class ExtensionPoint {

	private $namespace = '';

	private $extensionClass = '';

	private $extensions;

	public function __construct($namespace, $extensionClass) {
		$this->namespace = $namespace;
		$this->extensionClass = $extensionClass;
		$this->extensions = new Vector();
	}

	public function getNamespace() {
		return $this->namespace;
	}

	public function getExtensionClass() {
		return $this->extensionClass;
	}

	/**
	 * Returns all found extension point elements.
	 *
	 * @return Vector of Extension
	 */
	public function getExtensions() {
		return $this->extensions;
	}

	public function addExtension(Extension $e) {
		$this->extensions[] = $e;
	}

	public function createExtension(ExtensionPointElement $epe) {
		$this->extensions[] = new $this->extensionClass($epe);
	}
}

/**
 * Interface to access extension point data.
 */
interface ExtensionPointElement {
	public function getName();
	public function getChildren();
	public function getChildrenByName($name);
	public function getValue();
	public function getAttributes();
	public function getAttribute($key);
}

/**
 * Simple xml implementation of extension point element.
 * 
 * @see ExtensionPointElement
 */
class XMLExtensionPointElement implements ExtensionPointElement {

	/**
	 * @var SimpleXMLElement
	 */
	private $xmlElement;

	private $name;

	/**
	 * @var Vector
	 */
	private $children;

	private $value;

	/**
	 * @var Vector
	 */
	private $attributes;

	public function __construct(SimpleXMLElement $xml) {
		$this->xmlElement = $xml;
		$this->name = $xml->getName();
		$this->children = new Vector();
		foreach ($xml->children() as $child) {
			$this->children[] = new self($child);
		}
		$this->value = (string) $xml;
		$this->attributes = new Vector();
		foreach ($xml->attributes() as $key => $value) {
			$this->attributes[$key] = (string) $value;
		}
	}

	public function getName() {
		return $this->name;
	}

	public function getChildren() {
		return $this->children;
	}

	public function getChildrenByName($name) {
		$children = new Vector();
		foreach ($this->xmlElement-> $name as $child) {
			$children = new self($child);
		}
		return $children;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function getAttribute($key) {
		return isset ($this->attributes[$key]) ? $this->attributes[$key] : null;
	}

	public function getValue() {
		return $this->value;
	}

	public function __toString() {
		return $this->value;
	}
}

class ModuleNotFoundException extends Exception {
}

/**
 * Exception which gets thrown if there are
 * any unresolved dependencies.
 */
class ModuleMissingException extends Exception {
}

class Chameleon {

	const MODULE_XML = 'module.xml';
	const MODULE_PHP = 'module.php';

	private $path;
	
	/**
	 * @var ExtensionRegistry
	 */
	private $extensionRegistry;
	
	/**
	 * @var Vector
	 */
	private $disabledModules;
	
	private $logLevel;
	
	/**
	 * @var Log
	 */
	private $log;

	/**
	 * Cache with all loaded modules.
	 * 
	 * @var Vector
	 */
	private $modules;

	public function __construct($path) {
		$this->path = $path;
		$this->extensionRegistry = new ExtensionRegistry();
		$this->disabledModules = new Vector();
		$this->modules = new Vector();

		// parse disabled modules
		$xml = simplexml_load_file('xml/ch.anomey.chameleon/configuration.xml');

		foreach ($xml->disable->bundle as $bundle) {
			if($bundleXml = @simplexml_load_file('bundles/' . $bundle . '/bundle.xml')) {
				foreach ($bundleXml->module as $module) {
					$this->disabledModules[] = (string) $module;
				}
			}
		}
		
		$this->logLevel = (int) $xml->logLevel;
		$this->log = new Log('ch.anomey.chameleon', $this->getLogLevel());

		// load modules in path
		foreach (scandir($this->path) as $module) {
			if ($this->isModule($module)) {
				try {
					$this->loadModule($module);
				} catch (ModuleMissingException $e) {
					$this->log->warn($e->getMessage());
				}
			}
		}
	}
	
	public function getExtensionRegistry() {
		return $this->extensionRegistry;
	}
	
	public function getLogLevel() {
		return $this->logLevel;
	}
	
	/**
	 * Invoke modules. If a parameter is passed,
	 * only this modules gets invoked. Otherwise
	 * all loaded modules gets invoked.
	 *
	 * @param string $module optional parameter to invoke only a specific module
	 */
	public function invoke($module = null) {
		if($module == null) {
			foreach($this->modules as $module) {
				$module->invoke();
			}
		} else {
			try {
				$this->getModule($module)->invoke();
			} catch (ModuleNotFoundException $e) {
			}
		}
	}

	/**
	 * Returns a module specified by the passed name.
	 * 
	 * @param string $name name of the wanted module
	 * @return Module module with the passed name
	 */
	public function getModule($name) {
		if ($this->modules->exists($name)) {
			return $this->modules[$name];
		} else {
			return $this->loadModule($name);
		}
	}
	
	/**
	 * Check if the modules path contains the passed module. Returns 
	 * the path to the module folder if it is a module or otherwise
	 * <code>false</code>.
	 *
	 * @param string $module name of the folder
	 * @return mixed 
	 */
	private function isModule($module) {
		$file = $this->path . '/' . $module . '/' . self::MODULE_XML;
		if (file_exists($file)) {
			return $this->path . '/' . $module;
		} else {
			return false;
		}
	}

	/**
	 * Loads a module specified by the passed name.
	 * 
	 * @param string $name name of the module to load
	 * @return Module module with the passed name
	 */
	private function loadModule($name) {
		if (!$this->disabledModules->contains($name)) { // only load modules which are not disabled
			if (!isset ($this->modules[$name])) { // only load module if not already loaded

				if ($moduleFolder = $this->isModule($name)) {

					$this->log->trail('Loading module \'' . $name . '\'.');

					// parse module xml file
					$xml = simplexml_load_file($moduleFolder . '/' . self::MODULE_XML);

					foreach ($xml->require->module as $requiredModule) {
						try {
							$this->loadModule((string) $requiredModule);
						} catch (ModuleNotFoundException $e) {
							throw new ModuleMissingException('Module \'' . $name . '\' requires module \'' . (string) $requiredModule . '\' which could not be loaded!');
						}
					}

					// include module.php of module
					if (file_exists($moduleFolder . '/' . self::MODULE_PHP)) {
						include_once $moduleFolder . '/' . self::MODULE_PHP;
					}

					$moduleClass = (string) $xml->class;

					// if no module class is defined, us default class
					if ($moduleClass == '') {
						$moduleClass = 'Module';
					}

					// create module
					$module = new $moduleClass ($this, $name);
					$this->modules[$name] = $module;

					// read extension points
					foreach ($xml->extensionPoint as $ep) {
						$extensionClass = (string) $ep->class;
						$namespace = (string) $ep->namespace;

						// add extension point to registry
						$this->extensionRegistry->addExtensionPoint(new ExtensionPoint($namespace, $extensionClass));
					}

					// loop all extension points
					foreach ($this->extensionRegistry->getExtensionPoints() as $extensionPoint) {
						$xml->registerXPathNamespace('t', $extensionPoint->getNamespace());

						// parse extensions of current extension point
						foreach ($xml->xpath('//t:extension') as $e) {
							// add extension to extension point
							$extensionPoint->createExtension(new XMLExtensionPointElement($e));
						}
					}
					
					$this->log->trail('Loaded module \'' . $name . '\'.');

					return $module;
				} else {
					throw new ModuleNotFoundException('Module \'' . $name . '\' not found!');
				}
			}
		}
	}
}
?>
