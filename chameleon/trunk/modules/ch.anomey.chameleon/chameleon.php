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

class Bundle {

	/**
	 * @var Chameleon
	 */
	private $chameleon;
	
	private $id;

	private $name;

	private $description = '';

	private $update;

	/**
	 * @var Vector
	 */
	private $modules;

	public function __construct(Chameleon $chameleon, $id, $name, $update = '') {
		$this->chameleon = $chameleon;
		$this->id = $id;
		$this->name = $name;
		$this->update = '';
		$this->modules = new Vector();
	}
	
	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getDescription() {
		return $this->description;
	}

	public function setDescription($description) {
		$this->description = $description;
	}

	public function getUpdate() {
		return $this->update;
	}

	public function getModules() {
		return $this->modules;
	}

	public function addModule(Module $module) {
		$this->modules->append($module);
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

	public function __construct(Chameleon $chameleon, $id) {
		$this->chameleon = $chameleon;
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
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
		$this->value = trim($xml);
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

class BundleNotFoundException extends Exception {
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

	const BUNDLE_XML = 'bundle.xml';

	private $modulesPath;

	private $bundlesPath;

	/**
	 * @var ExtensionRegistry
	 */
	private $extensionRegistry;

	/**
	 * @var Vector
	 */
	private $disabledBundles;

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
	 * Cache with all loaded bundles.
	 * 
	 * @var Vector
	 */
	private $bundles;

	public function __construct($modulesPath = 'modules', $bundlesPath = 'bundles') {
		$this->modulesPath = $modulesPath;
		$this->bundlesPath = $bundlesPath;
		$this->extensionRegistry = new ExtensionRegistry();
		$this->disabledBundles = new Vector();
		$this->disabledModules = new Vector();
		$this->modules = new Vector();
		$this->bundles = new Vector();

		// parse disabled modules
		$xml = simplexml_load_file('xml/ch.anomey.chameleon/configuration.xml');
		foreach ($xml->disable->bundle as $bundle) {
			$this->disabledBundles->append(trim($bundle));
		}

		$this->logLevel = (int) $xml->log->level;
		$this->log = new Log('ch.anomey.chameleon', $this->getLogLevel());

		// load bundles
		foreach (scandir($this->bundlesPath) as $bundle) {
			if ($this->isBundle($bundle)) {
				try {
					$this->loadBundle($bundle);
				} catch (ModuleMissingException $e) {
					$this->log->warn($e->getMessage());
				}
			}
		}

		// load modules in path
		foreach (scandir($this->modulesPath) as $module) {
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
	 * Check if the bundles path contains the passed bundle. Returns 
	 * the path to the bundle folder if it is a bundle or otherwise
	 * <code>false</code>.
	 *
	 * @param string $bundle id of the bundle
	 * @return mixed 
	 */
	private function isBundle($bundle) {
		$file = $this->bundlesPath . '/' . $bundle . '/' . self::BUNDLE_XML;
		if (file_exists($file)) {
			return $this->bundlesPath . '/' . $bundle;
		} else {
			return false;
		}
	}

	/**
	 * Loads a bundle specified by the passed id.
	 * 
	 * @param string $id id of the bundle to load
	 * @return Bundle bundle with the passed id
	 */
	private function loadBundle($id) {
		if (!isset ($this->bundles[$id])) { // only load bundle if not already loaded
			if ($bundleFolder = $this->isBundle($id)) {
				if ($this->disabledBundles->contains($id)) { // only load bundle which are not disabled
					$this->log->trail('Disable modules of disabled bundle \'' . $id . '\'.');
		
					// parse bundle xml file
					$xml = simplexml_load_file($bundleFolder . '/' . self::BUNDLE_XML);

					// read modules
					foreach ($xml->module as $module) {
						$this->disabledModules->append(trim($module));
						$this->log->trail('Disabled module \'' . trim($module) . '\'.');
					}

					$this->log->trail('Disabled modules of disabled bundle \'' . $id . '\'.');
				} else {
					$this->log->trail('Loading bundle \'' . $id . '\'.');

					// parse bundle xml file
					$xml = simplexml_load_file($bundleFolder . '/' . self::BUNDLE_XML);

					$name = trim($xml->name);
					$description = trim($xml->description);
					$update = trim($xml->update);

					// create module
					$bundle = new Bundle($this, $id, $name, $update);
					$bundle->setDescription($description);
					$this->bundles[$id] = $bundle;

					// read modules
					foreach ($xml->module as $module) {
						try {
							$bundle->addModule($this->getModule(trim($module)));
						} catch(ModuleNotFoundException $e) {
							$this->log->warn($e->getMessage());
						}
					}

					$this->log->trail('Loaded bundle \'' . $id . '\'.');

					return $bundle;
				}
			} else {
				throw new BundleNotFoundException('Bundle \'' . $id . '\' not found!');
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
			return $this->loadModule($id);
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
		$file = $this->modulesPath . '/' . $module . '/' . self::MODULE_XML;
		if (file_exists($file)) {
			return $this->modulesPath . '/' . $module;
		} else {
			return false;
		}
	}

	/**
	 * Loads a module specified by the passed name.
	 * 
	 * @param string $id id of the module to load
	 * @return Module module with the passed id
	 */
	private function loadModule($id) {
		if (!$this->disabledModules->contains($id)) { // only load modules which are not disabled
			if (!isset ($this->modules[$id])) { // only load module if not already loaded

				if ($moduleFolder = $this->isModule($id)) {

					$this->log->trail('Loading module \'' . $id . '\'.');

					// parse module xml file
					$xml = simplexml_load_file($moduleFolder . '/' . self::MODULE_XML);

					foreach ($xml->require->module as $requiredModule) {
						try {
							$this->loadModule(trim($requiredModule));
						} catch (ModuleNotFoundException $e) {
							throw new ModuleMissingException('Module \'' . $id . '\' requires module \'' . trim($requiredModule) . '\' which could not be loaded!');
						}
					}

					// include module.php of module
					if (file_exists($moduleFolder . '/' . self::MODULE_PHP)) {
						include_once $moduleFolder . '/' . self::MODULE_PHP;
					}

					$moduleClass = trim($xml->class);

					// if no module class is defined, us default class
					if ($moduleClass == '') {
						$moduleClass = 'Module';
					}

					// create module
					$module = new $moduleClass ($this, $id);
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
							$extensionPoint->createExtension(new XMLExtensionPointElement($e));
						}
					}

					$this->log->trail('Loaded module \'' . $id . '\'.');

					return $module;
				} else {
					throw new ModuleNotFoundException('Module \'' . $id . '\' not found!');
				}
			}
		}
	}
}
?>
