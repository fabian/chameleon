<?php

require_once 'util.php';
require_once 'log.php';

/**
 * Central registry of all extensions, extension points
 * and namespaces. Every bundle owns a reference to this registry
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
 * The bundle class which can be overloaded by the bundles.
 * It makes the extension point registry avaible to the bundles
 * so they can find the defined extensions.
 *
 * @author Fabian Vogler
 */
class Bundle {

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

	public function getBundle($id) {
		return $this->chameleon->getBundle($id);
	}

	/**
	 * This method can be overloaded. It get's called after all
	 * bundles are loaded if the chameleon user want's so.
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
	 * @var Bundle
	 */
	private $bundle = null;

	/**
	 * @return Bundle
	 */
	public function getBundle() {
		return $this->bundle;
	}

	public function setBundle(Bundle $bundle) {
		$this->bundle = $bundle;
	}

	/**
	 * Method which can be overloaded to read information
	 * from the extension point element. Gets called after
	 * all attributes of Extension are set.
	 */
	public function load(ExtensionPointElement $element) {

	}
}

class StandardExtension extends Extension  {

	private $class;

	public function getClass() {
		return $this->class;
	}

	public function load(ExtensionPointElement $element) {
		$this->class = trim($element->getValue());
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

	public function createExtension(ExtensionPointElement $epe, Bundle $bundle) {
		$extension = new $this->extensionClass();
		$extension->setBundle($bundle);
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

class BundleNotFoundException extends Exception {
}

/**
 * Exception which gets thrown if there are
 * any unresolved dependencies.
 */
class BundleMissingException extends Exception {
}

class Chameleon {

	const BUNDLES = 'bundles';

	const BUNDLE_XML = 'bundle.xml';

	const BUNDLE_PHP = 'bundle.php';

	/**
	 * @var ExtensionRegistry
	 */
	private $extensionRegistry;

	/**
	 * @var Vector
	 */
	private $disabledBundles;

	private $logLevel;

	/**
	 * @var Log
	 */
	private $log;

	/**
	 * Cache with all loaded bundles.
	 *
	 * @var Vector
	 */
	private $bundles;

	/**
	 * @var Vector
	 */
	private $folders;

	public function __construct() {
		$this->extensionRegistry = new ExtensionRegistry();
		$this->disabledBundles = new Vector();
		$this->bundles = new Vector();
		$this->folders = new Vector();

		// parse disabled bundles
		$xml = simplexml_load_file('data/ch.anomey.chameleon/configuration.xml');
		foreach ($xml->disable->bundle as $bundle) {
			$this->disabledBundles->append(trim($bundle));
		}

		$this->logLevel = (int) $xml->log->level;
		$this->log = new Log('ch.anomey.chameleon', $this->getLogLevel());

		// load bundles folder in path
		foreach (scandir(self::BUNDLES) as $bundle) {
			$path = self::BUNDLES . '/' . $bundle;
			if ($this->isBundle($path)) {
				$this->log->trail('Loading bundle folder \'' . $bundle . '\'.');

				// parse bundle xml file
				$xml = simplexml_load_file($path . '/' . self::BUNDLE_XML);

				$id = (string) $xml['id'];
				$version = (string) $xml['version'];

				if($version !== '') {
					if(!$this->folders->exists($id)) {
						$this->folders[$id] = new Vector();
					}
					$this->folders[$id][$version] = $path;

					$this->log->trail(sprintf('Loaded bundle folder \'%s\'.', $bundle));
				} else {
					$this->log->warn(sprintf('Could no load bundle folder \'%s\' because bundle has no version.', $bundle));
				}
			}
		}

		foreach($this->folders as $id => $versions) {
			$this->loadBundle($id);
		}
	}

	public function getExtensionRegistry() {
		return $this->extensionRegistry;
	}

	public function getLogLevel() {
		return $this->logLevel;
	}

	/**
	 * Invoke bundles. If a parameter is passed,
	 * only this bundles gets invoked. Otherwise
	 * all loaded bundles gets invoked.
	 *
	 * @param string $bundle optional parameter to invoke only a specific bundle
	 */
	public function invoke($bundle = null) {
		if($bundle == null) {
			foreach($this->bundles as $bundle) {
				$bundle->invoke();
			}
		} else {
			try {
				$this->getBundle($bundle)->invoke();
			} catch (BundleNotFoundException $e) {
			}
		}
	}

	/**
	 * Returns a bundle specified by the passed id.
	 *
	 * @param string $id id of the wanted bundle
	 * @return Bundle bundle with the passed id
	 */
	public function getBundle($id) {
		if ($this->bundles->exists($id)) {
			return $this->bundles[$id];
		} else {
			throw new BundleNotFoundException(sprintf('Bundle \'%s\' not found!', $id));
		}
	}

	/**
	 * Check if the passsed path is a bundle. Returns
	 * <code>true</code> if it is a bundle or otherwise
	 * <code>false</code>.
	 *
	 * @param string $path name of the folder
	 * @return boolean
	 */
	private function isBundle($path) {
		return file_exists($path . '/' . self::BUNDLE_XML);
	}

	/**
	 * Loads a bundle specified by the passed id.
	 *
	 * @param string $id id of the bundle to load
	 */
	private function loadBundle($id) {
		if(!isset($this->bundles[$id])) { // only load bundle if not already loaded
			if(!$this->disabledBundles->contains($id)) {
				if($this->folders->exists($id)) {
					$this->log->trail(sprintf('Loading bundle \'%s\'.', $id));

					$previous = 0;
					$latest = '';

					foreach($this->folders[$id] as $version => $path) {
						if(version_compare($previous, $version, '<') or $latest == '') {
							$latest = $path;
						}
						$previous = $version;
					}

					$bundleFolder = $latest;

					// parse bundle xml file
					$xml = simplexml_load_file($bundleFolder . '/' . self::BUNDLE_XML);

					$version = (string) $xml['version'];

					if($xml->require->bundle != null) {
						foreach ($xml->require->bundle as $requiredBundle) {
							try {
								$this->loadBundle(trim($requiredBundle));
							} catch (BundleNotFoundException $e) {
								throw new BundleMissingException('Bundle \'' . $id . '\' requires bundle \'' . trim($requiredBundle) . '\' which could not be loaded!');
							}
						}
					}

					// include bundle.php of bundle
					if (file_exists($bundleFolder . '/' . self::BUNDLE_PHP)) {
						include_once $bundleFolder . '/' . self::BUNDLE_PHP;
					}

					$bundleClass = trim($xml->class);

					// if no bundle class is defined, use default class
					if ($bundleClass == '') {
						$bundleClass = 'Bundle';
					}

					// create bundle
					$bundle = new $bundleClass($this, $id, $version);
					$this->bundles[$id] = $bundle;

					// read extension points
					foreach ($xml->extensionPoint as $ep) {
						$extensionClass = trim($ep->class);
						if($extensionClass == '') {
							$extensionClass = 'StandardExtension';
						}
						
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
							$extensionPoint->createExtension(new XMLExtensionPointElement($e), $bundle);
						}
					}

					$this->log->trail(sprintf('Loaded bundle \'%s\' version \'%s\'.', $bundle->getId(), $bundle->getVersion()));
				} else {
					$this->log->error(sprintf('Could not load bundle \'%s\' because no folder contains the bundle.', $id));
				}
			} else {
				$this->log->trail(sprintf('Bundle \'%s\' hasn\'t been loaded as it\'s disabled.', $id));
			}
		}
	}
}

class AnomeyPreInvokeExtension extends Extension {

	private $class;

	public function getClass() {
		return $this->class;
	}

	public function load(ExtensionPointElement $element) {
		$this->class = trim($element->getValue());
	}
}

interface AnomeyPreInvoke {
	public function __construct(Chameleon $chameleon);
	public function invoke();
}

class AnomeyMediaExtension extends StandardExtension {
	
}

interface AnomeyMedia {
	public function __construct(Chameleon $chameleon);
	public function isActive();
	public function handle();
}

?>
