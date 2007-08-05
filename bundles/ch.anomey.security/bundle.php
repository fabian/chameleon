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
	 * Returns the groups the user is assigned to.
	 * 
	 * @return Vector
	 */
	public function getGroups();
}

interface Group {

	/**
	 * Returns the id of the role. A unique string.
	 *
	 * @return string
	 */
	public function getId();

	/**
	 * Returns a describing name of the group. 
	 *
	 * @return string
	 */
	public function getName();
}

/**
 * Interface for security providers.
 */
interface SecurityProvider {
	
	/**
	 * Returns users.
	 *
	 * @return Vector
	 */
	public function getUsers();
	
	/**
	 * Returns groups.
	 *
	 * @return Vector
	 */
	public function getGroups();

	/**
	 * Tries to authenticate the user with the passed request.
	 *
	 * @param Request $request the request to authenticate
	 * @return mixed <code>false</code> on failure, otherwise the Role object
	 */
	public function authenticate(Request $request);
}

class Resource {
	
	private $id;
	
	private $name;
	
	public function __construct($id, $name) {
		$this->id = $id;
		$this->name = $name;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getName() {
		return $this->name;
	}
}

class SecurityBundle extends Bundle implements SecurityProvider {

	/**
	 * @var Log
	 */
	private $log;

	/**
	 * @var Vector
	 */
	private $providers;
	
	/**
	 * @var Vector
	 */
	private $resources;
	
	public function invoke() {
		$this->log = new Log($this->getId(), $this->getLogLevel());
		$this->providers = new Vector();
		$this->resources = new Vector();

		if(!$this->getExtensions('http://anomey.ch/security/provider')->count() > 0) {
			$this->log->error('No security provider implementation found!');
		} else {
			foreach($this->getExtensions('http://anomey.ch/security/provider') as $extension) {
				$class = $extension->getProvider();
				$this->providers[] = new $class;
			}
		}

		foreach($this->getExtensions('http://anomey.ch/security/resources') as $extension) {
			$resources = $extension->getResources();
			$this->resources->merge($resources);
		}
	}
	
	/**
	 * Returns all users.
	 *
	 * @return Vector
	 */
	public function getUsers() {
		$users = new Vector();
		foreach($this->providers as $provider) {
			$users->merge($provider->getUsers());
		}
		return $users;
	}

	/**
	 * Returns a user.
	 *
	 * @param string $id ID of the user.
	 * @return User
	 */
	public function getUser($id) {
		foreach($this->getUsers() as $user) {
			if($user->getId() == $id) {
				return $user;
			}
		}
		return null;
	}
	
	/**
	 * Returns all users.
	 *
	 * @return Vector
	 */
	public function getGroups() {
		$groups = new Vector();
		foreach($this->providers as $provider) {
			$groups->merge($provider->getGroups());
		}
		return $groups;
	}

	public function getResources() {
		return $this->resources;
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

class ResourcesExtension extends Extension {

	/**
	 * @var Vector
	 */
	private $resources;

	public function getResources() {
		return $this->resources;
	}

	public function load(ExtensionPointElement $element) {
		$this->resources = new Vector();
		foreach($element->getChildren() as $resource) {
			$this->resources->append(new Resource($resource->getAttribute('id'), $resource->getAttribute('name')));
		}
	}
}

class SecurityProviderExtension extends Extension {

	/**
	 * @var string
	 */
	private $provider;

	public function getProvider() {
		return $this->provider;
	}

	public function load(ExtensionPointElement $element) {
		$this->provider = $element->getChildrenByName('provider')->getValue();
	}
}

?>
