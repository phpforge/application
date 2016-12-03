<?php

namespace Forge\Application;

use Forge\Application;

/**
 * View
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 */
class View {

	/**
	 * Get application directory
	 *
	 * @return string
	 */
	public function getAppDir() {
		return Application::getAppDir();
	}

	/**
	 * Get base URI
	 *
	 * @return string
	 */
	public function getBaseUri() {
		return Application::getBaseUri();
	}
	
	/**
	 * Get application URL
	 *
	 * @return string
	 */
	public function getAppUrl() {
		return Application::getAppUrl();
	}

	/**
	 * Get session
	 *
	 * @param string $key Session key
	 *
	 * @return mixed
	 */
	public function getSession($key) {
		return Application::getSession($key);
	}

	/**
	 * Set session
	 *
	 * @param string $key   Key
	 * @param mixed  $value Value
	 */
	public function setSession($key, $value) {
		return Application::setSession($key, $value);
	}

	/**
	 * @var string
	 */
	protected $template;

	/**
	 * Set template
	 *
	 * @param string $template Template
	 *
	 * @return \Forge\Application\View
	 */
	public function setTemplate($template) {
		$this->template = $template;
		return $this;
	}

	/**
	 * @var boolean
	 */
	protected $render = true;

	/**
	 * @var boolean
	 */
	private $rendered = false;

	/**
	 * Set render
	 *
	 * @param boolean $render
	 *
	 * @return \Forge\Application\View
	 */
	public function setRender($render) {
		$this->render = $render;
		return $this;
	}

	/**
	 * Create var
	 *
	 * @param string $key   Key
	 * @param mixed  $value Value
	 */
	public function createVar($key, $value) {
		$this->$key = $value;
	}

	/**
	 * Render
	 *
	 * @return mixed
	 */
	public function render() {
		if ($this->render && !$this->rendered && file_exists($this->template)) {
			$this->rendered = true;

			foreach($this as $key => $value) {
				${$key} = $value;
			}

			ob_start();
			include $this->template;
			$content = ob_get_clean();
			return $content;
		}

		return false;
	}

	/**
	 * Save
	 *
	 * @param string $target Target file
	 *
	 * @throws View\Exception
	 */
	public function save($target) {
		$fp = fopen($target, 'w');
		if (fwrite($fp, $this->render()) === FALSE) {
			throw new Exception('Unable to save view');
		}
		fclose($fp);
	}
}