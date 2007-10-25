<?php

// This file loads the main chameleon classes

class Loader {

	const BUNDLES = 'bundles';

	private $bundle;

	private $file;

	private function __construct($ini) {
		$config = parse_ini_file($ini);

		list($this->bundle, $this->file) = explode('/', $config['chameleon.start']);
	}

	public static function init($ini) {
		return new Loader($ini);
	}

	public function start() {
		$previous = 0;
		$latest = '';

		$bundlesPath = dirname(__FILE__) . '/' . self::BUNDLES;
		foreach (scandir($bundlesPath) as $bundle) {
			$path = $bundlesPath . '/' . $bundle;
			$file = $path . '/bundle.xml';

			if(file_exists($file)) {
				// parse bundle xml file
				$xml = simplexml_load_file($file);

				$id = (string) $xml['id'];
				$version = (string) $xml['version'];

				if($version !== '') {
					if($id == $this->bundle) {
						if(version_compare($previous, $version, '<') or $latest == '') {
							$latest = $path;
						}
						$previous = $version;
					}
				}
			}
		}

		require_once $latest . '/' . $this->file;
	}
}

Loader::init('configuration/config.ini')->start();

?>
