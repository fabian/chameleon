<?php

echo '<br/>Users: ';
foreach($this->getModule('ch.anomey.security')->getUsers() as $user) {
	printf('%s, ', $user->getNick());
}
echo '<br/>Groups: ';
foreach($this->getModule('ch.anomey.security')->getGroups() as $group) {
	printf('%s, ', $group->getName());
}
echo '<br/>Ressources: ';
foreach($this->getModule('ch.anomey.security')->getResources() as $resource) {
	printf('%s (%s), ', $resource->getId(), $resource->getName());
}

?>
