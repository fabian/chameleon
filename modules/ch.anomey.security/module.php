<?php

interface User {

	/**
	 * Returns the id of the user. A unique string.
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Returns the user's nick name. 
	 *
	 * @return string
	 */
	public function getNick();
	
	/**
	 * Returns the roles the user is assigned to.
	 * 
	 * @return Vector
	 */
	public function getRoles();
	
	/**
	 * Returns <code>true</code> if the user is assigned in any 
	 * way to the passed role and otherwise <code>false</code>.
	 * 
	 * @return boolean
	 */
	public function hasRole(Role $role);
}

interface Role {

	/**
	 * Returns the id of the role. A unique string.
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Returns a describing name of the role. 
	 *
	 * @return string
	 */
	public function getName();
	
	/**
	 * Returns the parent of the role to inherit from 
	 * or <code>null</code> if there is no parent role.
	 *
	 * @var Role
	 */
	public function getParent();
	
	/**
	 * Returns <code>true</code> if the role inherits from the  
	 * passed role in any way and otherwise <code>false</code>.
	 * 
	 * @return boolean
	 */
	public function hasRole(Role $role);
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
	 * Tries to authenticate the user with the passed request.
	 *
	 * @param Request $request the request to authenticate
	 * @return mixed <code>false</code> on failure, otherwise the Role object
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
