<?php

class TextUser implements User {

	/**
	 * The user's nick name. 
	 *
	 * @var string
	 */
	private $nick;
	
	/**
	 * @var Vector
	 */
	private $groups;
	
	public function __construct($nick) {
		$this->nick = $nick;
		$this->groups = new Vector();
	}

	public function getId() {
		return 'text/' . $this->nick;
	}

	public function getNick() {
		return $this->nick;
	}
	
	public function getGroups() {
		return $this->groups;
	}
	
	public function addGroup(TextGroup $group) {
		if(!$this->groups->contains($group)) {
			$this->groups->append($group);
			$group->addUser($this);
		}
	}
}

class TextGroup implements Group {

	/**
	 * The name of the group. 
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * @var Vector
	 */
	private $users;
	
	public function __construct($name) {
		$this->name = $name;
		$this->users = new Vector();
	}

	public function getId() {
		return 'text/' . $this->name;
	}

	public function getName() {
		return $this->name;
	}
	
	public function getUsers() {
		return $this->users;
	}
	
	public function addGroup(TextUser $user) {
		if(!$this->users->contains($user)) {
			$this->users->append($user);
			$user->addGroup($this);
		}
	}
}

/**
 * Implementation of the security provider interface with XML files.
 * Users and groups get stored in simple text files.
 */
class TextSecurityProvider implements SecurityProvider  {

	/**
	 * @var Vector
	 */
	private $users;

	/**
	 * @var Vector
	 */
	private $groups;
	
	public function __construct() {
		
		$this->users = new Vector();
		if(is_readable('data/ch.anomey.security.text/users.properties')) {
			$users = parse_ini_file('data/ch.anomey.security.text/users.properties', true);
			foreach($users as $username => $password) {
				$user = new TextUser($username);
				$this->users->set($user->getId(), $user);
			}
		}

		$this->groups = new Vector();
		if(is_readable('data/ch.anomey.security.text/groups.properties')) {
			$users = parse_ini_file('data/ch.anomey.security.text/groups.properties', true);
			foreach($users as $groupname => $users) {
				$group = new TextGroup($groupname);
				
				// load users into group
				foreach(explode(',', $users) as $username) {
					$username = trim($username);
					
					if($this->users->exists($username)) {
						$user = $this->users->get($username);
						$group->addUser($user);
					}
				}
				
				$this->groups->set($group->getName(), $group);
			}
		}
	}

	public function getUsers() {
		return $this->users->getValues();
	}

	public function getGroups() {
		return $this->groups->getValues();
	}

	/**
	 * Tries to authenticate the role with the parameters
	 * username and password.
	 *
	 * @param Request $request the request to authenticate
	 * @return mixed <code>false</code> on failure, otherwise the role object
	 */
	public function authenticate(Request $request) {
		return false;
	}
}

class SecurityTextBundle extends Bundle {
	public function invoke() {

	}
}

?>