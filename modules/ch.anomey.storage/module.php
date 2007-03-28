<?php

interface Storable {

}

class XMLStoreage implements Storable {

	private $file;

	private $objects = array();

	public function __construct($file) {
		$this->file = $file;
	}

	public function store(Storable $object) {
		$id = spl_object_hash($object);
		$this->objects[$id] = $object;
		$this->save();
	}

	/**
	 * Adds object to internal memory to be saved.
	 */
	public function prepare($object) {
		$id = spl_object_hash($object);
		$this->objects[$id] = $object;
	}

	public function search($query) {
		$matches = array();
		foreach ($this->objects as $object) {
			if(eval('return ' . $query . ';')) {
				$matches[] = $object;
			}
		}
		return $matches;
	}

	/**
	 * Saves objects in internal memory.
	 */
	public function save() {
		foreach ($this->objects as $object) {
			
		}
	}
}

?>
