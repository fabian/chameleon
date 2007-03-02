<?php

class View {

	private $data = array ();

	private $id;
	
	private $template;

	/**
	 * @var AnomeyViewModule
	 */
	private $module;

	private $odd = 1;

	public function __construct(AnomeyViewModule $module, $id, $template) {
		$this->module = $module;
		$this->id = $id;
		$this->template = $template;
	}

	public function getTemplate() {
		return $this->template;
	}

	public function display() {
		$this->show($this->getTemplate());
	}

	public function getModule($id) {
		return $this->module->getModule($id);
	}
	
	private function show($template) {
		$this->data['id'] = $this->id;
		$this->data['template'] = $template;
		include $template;
	}

	private function odd($name = 'odd') {
		echo $this->odd++ % 2 ? $name : '';
	}

	private $layouts = array ();

	private function layout($id) {
		$this->layouts[] = $this->module->getView($id);
		ob_start();
	}

	private function endlayout() {
		$content = ob_get_clean();
		include array_pop($this->layouts)->getTemplate();
	}

	public static function out(& $variable, $default = '') {
		if (isset ($variable)) {
			echo $variable;
		} else {
			echo $default;
		}
	}

	public function __set($key, $value) {
		$this->data[$key] = $value;
	}

	public function __get($key) {
		return isset ($this->data[$key]) ? $this->data[$key] : '';
	}
}

class AnomeyViewModule extends Module {

	/**
	 * @var Vector
	 */
	private $views;

	public function invoke() {
		$this->views = new Vector();

		if(!$this->getExtensions('http://anomey.ch/view')->count() > 0) {
			$this->log->error('No views found!');
		} else {
			foreach($this->getExtensions('http://anomey.ch/view') as $extension) {
				$this->views->set($extension->getId(), $extension->getTemplate());
			}
		}
	}

	/**
	 * @param Module $module
	 * @param unknown_type $id
	 * @return View
	 */
	public function getView($id) {
		if($this->views->exists($id)) {
			return new View($this, $id, $this->views->get($id));
		}
		return null;
	}
	
	public function display($id) {
		if($this->getView($id) != null) {
			$this->getView($id)->display();
		}
	}
}

class AnomeyViewExtension extends Extension {

	/**
	 * @var string
	 */
	private $id;

	public function getId() {
		return $this->id;
	}

	/**
	 * @var string
	 */
	private $template;

	public function getTemplate() {
		return $this->template;
	}

	public function load(ExtensionPointElement $element) {
		$this->id = trim($element->getChildrenByName('view')->getAttribute('id'));
		$this->template = 'modules/' . $this->getModule()->getId() . '/' . trim($element->getChildrenByName('view')->getValue());
	}
}

?>
