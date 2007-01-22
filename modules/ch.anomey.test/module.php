<?php

class AnomeyTestModule extends Module {
	public function invoke() {
		echo '<br/>Users: ';
		foreach($this->getModule('ch.anomey.security')->getUsers() as $user) {
			echo $user->getName() . ', ';
		}
		echo '<br/>Groups of user u1: ';
		foreach($this->getModule('ch.anomey.security')->getUser('u1')->getGroups() as $group) {
			echo $group->getName() . ', ';
		}
	}
}

?>