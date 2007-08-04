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

	private $id;
	
	private $version;

	public function __construct(Chameleon $chameleon, $id, $version) {
		$this->chameleon = $chameleon;
		$this->id = $id;
		$this->version = $version;
	}

	public function getId() {
		return $this->id;
	}
	
	public function getVersion() {
		return $this->version;
	}

	public function getLogLevel() {
		return $this->chameleon->getLogLevel();
	}

	public function getModule($id) {
		return $this->chameleon->getModule($id);
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

	/*
	 * @var Module
	 */
	private $module = null;

	/**
	 * @return Module
	 */
	public function getModule() {
		return $this->module;
	}

	public function setModule(Module $module) {
		$this->module = $module;
	}

	/**
	 * Method which can be overloaded to read information
	 * from the extension point element. Gets called after
	 * all attributes of Extension are set.
	 */
	public function load(ExtensionPointElement $element) {

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

	public function createExtension(ExtensionPointElement $epe, Module $module) {
		$extension = new $this->extensionClass();
		$extension->setModule($module);
		$extension->load($epe);
		$this->extensions[] = $extension;
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
		$this->value = trim((string) $xml);
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
		foreach ($this->xmlElement->$name as $child) {
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

	private $modulesPath;

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
	
	/**
	 * @var Vector
	 */
	private $folders; 
	
	public function __construct($modulesPath = 'modules') {
		$this->modulesPath = $modulesPath;
		$this->extensionRegistry = new ExtensionRegistry();
		$this->disabledModules = new Vector();
		$this->modules = new Vector();
		$this->folders = new Vector();

		// parse disabled modules
		$xml = simplexml_load_file('data/ch.anomey.chameleon/configuration.xml');
		foreach ($xml->disable->module as $module) {
			$this->disabledModules->append(trim($module));
		}
		
		$this->logLevel = (int) $xml->log->level;
		$this->log = new Log('ch.anomey.chameleon', $this->getLogLevel());
		
		// load module folder in path
		foreach (scandir($this->modulesPath) as $module) {
			$path = $this->modulesPath . '/' . $module;
			if ($this->isModule($path)) {
				$this->log->trail('Loading module folder \'' . $module . '\'.');

				// parse module xml file
				$xml = simplexml_load_file($path . '/' . self::MODULE_XML);
				
				$id = (string) $xml['id'];
				$version = (string) $xml['version'];
				
				if($version !== '') {
					if(!$this->folders->exists($id)) {
						$this->folders[$id] = new Vector();
					}
					$this->folders[$id][$version] = $path;
	
					$this->log->trail(sprintf('Loaded module folder \'%s\'.', $module));
				} else {
					$this->log->warn(sprintf('Could no load module folder \'%s\' because module has no version.', $module));
				}
			}
		}
		
		foreach($this->folders as $id => $versions) {			
			$this->loadModule($id);
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
	 * Returns a module specified by the passed id.
	 *
	 * @param string $id id of the wanted module
	 * @return Module module with the passed id
	 */
	public function getModule($id) {
		if ($this->modules->exists($id)) {
			return $this->modules[$id];
		} else {
			throw new ModuleNotFoundException(sprintf('Module \'%s\' not found!', $id));
		}
	}

	/**
	 * Check if the passsed path is a module. Returns
	 * <code>true</code> if it is a module or otherwise
	 * <code>false</code>.
	 *
	 * @param string $path name of the folder
	 * @return boolean
	 */
	private function isModule($path) {
		return file_exists($path . '/' . self::MODULE_XML);
	}

	/**
	 * Loads a module specified by the passed id.
	 *
	 * @param string $id id of the module to load
	 */
	private function loadModule($id) {
		if(!isset($this->modules[$id])) { // only load module if not already loaded
			if(!$this->disabledModules->contains($id)) {
				if($this->folders->exists($id)) {
					$this->log->trail(sprintf('Loading module \'%s\'.', $id));
					
					$previous = 0;
					$latest = '';
					
					foreach($this->folders[$id] as $version => $path) {
						if(version_compare($previous, $version, '<') or $latest == '') {
							$latest = $path;
						}
						$previous = $version;
					}
					
					$moduleFolder = $latest;
					
					// parse module xml file
					$xml = simplexml_load_file($moduleFolder . '/' . self::MODULE_XML);

					$version = (string) $xml['version'];
					
					if($xml->require->module != null) {
						foreach ($xml->require->module as $requiredModule) {
							try {
								$this->loadModule(trim($requiredModule));
							} catch (ModuleNotFoundException $e) {
								throw new ModuleMissingException('Module \'' . $id . '\' requires module \'' . trim($requiredModule) . '\' which could not be loaded!');
							}
						}
					}
			
					// include module.php of module
					if (file_exists($moduleFolder . '/' . self::MODULE_PHP)) {
						include_once $moduleFolder . '/' . self::MODULE_PHP;
					}
					
					$moduleClass = trim($xml->class);
					
					// if no module class is defined, use default class
					if ($moduleClass == '') {
						$moduleClass = 'Module';
					}
					
					// create module
					$module = new $moduleClass($this, $id, $version);
					$this->modules[$id] = $module;
			
					// read extension points
					foreach ($xml->extensionPoint as $ep) {
						$extensionClass = trim($ep->class);
						$namespace = trim($ep->namespace);
			
						// add extension point to registry
						$this->extensionRegistry->addExtensionPoint(new ExtensionPoint($namespace, $extensionClass));
					}
			
					// loop all extension points
					foreach ($this->extensionRegistry->getExtensionPoints() as $extensionPoint) {
						$xml->registerXPathNamespace('t', $extensionPoint->getNamespace());
			
						// parse extensions of current extension point
						foreach ($xml->xpath('//t:extension') as $e) {
							// add extension to extension point
							$extensionPoint->createExtension(new XMLExtensionPointElement($e), $module);
						}
					}
	
					$this->log->trail(sprintf('Loaded module \'%s\' version \'%s\'.', $module->getId(), $module->getVersion()));
				} else {
					$this->log->error(sprintf('Could not load module \'%s\' because no folder contains the module.', $id));
				}
			} else {
				$this->log->trail(sprintf('Module \'%s\' hasn\'t been loaded as it\'s disabled.', $id));
			}
		}
	}
}
?>
