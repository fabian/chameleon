<?php

/**
 * User class which represents a user of the system. A user
 * can have multiple groups.
 */
class User {

	/**
	 * The id of the user. Mostly an integer or a string.
	 *
	 * @var mixed
	 */
	private $id;

	/**
	 * Nickname of the user - used to identify the user.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * @var Vector
	 */
	private $groups;

	public function __construct() {
		$this->id = 0;
		$this->name = '';
		$this->groups = new Vector();
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getGroups() {
		return $this->groups;
	}

	public function addGroup(Group $group) {
		if(!$this->groups->contains($group)) {
			$this->groups->append($group);
			$group->addUser($this); // add user to group
		}
	}

	public function removeGroup(Group $group) {
		if($this->groups->contains($group)) {
			$this->groups->remove($group);
			$group->removeUser($this);
		}
	}
}

class Group {

	private $id;

	private $name;

	/**
	 * @var Vector
	 */
	private $users;

	public function __construct() {
		$this->id = 0;
		$this->name = '';
		$this->users = new Vector();
	}

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getUsers() {
		return $this->users;
	}

	public function removeUser(User $user) {
		if($this->users->contains($user)) {
			$user->removeGroup($this);
			$this->users->remove($user);
		}
	}

	public function addUser(User $user) {
		if(!$this->users->contains($user)) {
			$this->users->append($user);
			$user->addGroup($this); // add group to user
		}
	}
}

/**
 * Interface for security providers.
 */
interface AnomeySecurityProvider {

	/**
	 * Returns a Vector with all users.
	 *
	 * @return Vector
	 */
	public function getUsers();

	/**
	 * Returns a Vector with all groups.
	 *
	 * @return Vector
	 */
	public function getGroups();

	/**
	 * Tries to authenticate the user with the passed parameters.
	 *
	 * @param array $parameters array with authentication parameters
	 * @return mixed <code>false</code> on failure, otherwise the User object
	 */
	public function authenticate($parameters);
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
		$this->log = new Log($this->getName(), $this->getLogLevel());
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
	 * @param string $id
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

	public function getGroups() {
		$groups = new Vector();
		foreach($this->providers as $provider) {
			$groups->merge($provider->getGroups());
		}
		return $groups;
	}

	/**
	 * Returns a group.
	 *
	 * @param string $id
	 * @return Group
	 */
	public function getGroup($id) {
		foreach($this->getGroups() as $group) {
			if($$group->getId() == $id) {
				return $group;
			}
		}
		return null;
	}

	public function authenticate($parameters) {
		foreach($this->providers as $provider) {
			$user = $provider->authenticate($parameters);
			if($user !== false) {
				return $user;
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

	public function __construct(ExtensionPointElement $element) {
		$this->provider = (string) $element->getChildrenByName('provider')->getValue();
	}
}

?>
