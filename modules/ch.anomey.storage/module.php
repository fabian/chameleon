<?php

/**
 * Factory class for the Storable interface.
 */
abstract class Storage {

	/**
	 * Opens a file storage.
	 *
	 * @param string $file
	 * @return ObjectContainer
	 */
	public static function openFile($file) {
		return new XMLObjectContainer($file);
	}
}

interface Storable {

}

interface ObjectContainer {
	public function set(Storable $object);
	public function commit();
	public function search($query);
}

class XMLObjectContainer implements ObjectContainer {

	private $file;

	private $autocommit = true;

	private $objects = array();

	public function __construct($file) {
		$this->file = $file;
	}

	public function set(Storable $object) {
		$id = spl_object_hash($object);
		$this->objects[$id] = $object;
		if($this->autocommit) {
			$this->commit();
		}
	}

	/**
	 * Saves objects in internal memory.
	 */
	public function commit() {
		foreach ($this->objects as $object) {
			
		}
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
}

?>
