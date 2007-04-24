<?php

class Role {

	/**
	 * The id of the role. A unique string.
	 *
	 * @var mixed
	 */
	private $id;

	/**
	 * Describing name of the role. 
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The parent role to inherit from. <code>null</code> if
	 * there is no parent.
	 *
	 * @var Role
	 */
	private $parent;
	
	/**
	 * @var Vector
	 */
	private $childs;
	
	public function __construct($id, $name, $parent = null) {
		$this->id = $id;
		$this->name = $name;
		$this->parent = $parent;
		$this->childs = new Vector();
		
		if($parent != null) {
			$parent->addChild($this);
		}
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}
	
	/**
	 * Returns the parent of the role or <code>null</code>
	 * if there is no parent.
	 * 
	 * @return Role
	 */
	public function getParent() {
		return $this->parent;
	}
	
	public function addChild(Role $role) {
		if(!$this->childs->contains($role)) {
			$this->childs->append($role);
		}
	}
}

class Resource {

	private $name;

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}
}

/**
 * Interface for security providers.
 */
interface AnomeySecurityProvider {

	/**
	 * Returns a Vector with all roles.
	 *
	 * @return Vector
	 */
	public function getRoles();

	public function getResources();

	/**
	 * Tries to authenticate the user with the passed request.
	 *
	 * @param Request $request the request to authenticate
	 * @return mixed <code>false</code> on failure, otherwise the User object
	 */
	public function authenticate(Request $request);
}

class AnomeySecurityModule extends Module implements AnomeySecurityProvider {

	/**
	 * @var Log
	 */
	private $log;

	/**
	 * @var Vector
	 */
	private $providers;

	public function invoke() {
		$this->log = new Log($this->getId(), $this->getLogLevel());
		$this->providers = new Vector();

		if(!$this->getExtensions('http://anomey.ch/security/provider')->count() > 0) {
			$this->log->error('No security provider implementation found!');
		} else {
			foreach($this->getExtensions('http://anomey.ch/security/provider') as $extension) {
				$class = $extension->getProvider();
				$this->providers[] = new $class;
			}
		}
	}

	public function getRoles() {
		$roles = new Vector();
		foreach($this->providers as $provider) {
			$roles->merge($provider->getRoles());
		}
		return $roles;
	}

	/**
	 * Returns a role.
	 *
	 * @param string $id
	 * @return Role
	 */
	public function getRole($id) {
		foreach($this->getRoles() as $role) {
			if($role->getId() == $id) {
				return $role;
			}
		}
		return null;
	}

	public function getResources() {
		$resources = new Vector();
		foreach($this->providers as $provider) {
			$resources->merge($provider->getResources());
		}
		return $resources;
	}

	public function authenticate(Request $request) {
		foreach($this->providers as $provider) {
			$role = $provider->authenticate($request);
			if($role !== false) {
				return $role;
			}
		}
		return false;
	}
}

class AnomeySecurityProviderExtension extends Extension {

	/**
	 * @var string
	 */
	private $provider;

	public function getProvider() {
		return $this->provider;
	}

	public function load(ExtensionPointElement $element) {
		$this->provider = trim($element->getChildrenByName('provider')->getValue());
	}
}

?>
