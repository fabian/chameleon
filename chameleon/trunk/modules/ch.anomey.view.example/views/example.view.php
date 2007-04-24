<?php

echo '<br/>Roles: ';
foreach($this->getModule('ch.anomey.security')->getRoles() as $role) {
	echo $role->getName() . ', ';
}
echo '<br/>Rersources: ';
foreach($this->getModule('ch.anomey.security')->getResources() as $resource) {
	echo $resource->getName() . ', ';
}

?>
