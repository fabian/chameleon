<?php

echo '<br/>Users: ';
foreach($this->getBundle('ch.anomey.security')->getUsers() as $user) {
	printf('%s, ', $user->getNick());
}
echo '<br/>Groups: ';
foreach($this->getBundle('ch.anomey.security')->getGroups() as $group) {
	printf('%s, ', $group->getName());
}
echo '<br/>Ressources: ';
foreach($this->getBundle('ch.anomey.security')->getResources() as $resource) {
	printf('%s (%s), ', $resource->getId(), $resource->getName());
}

?>
