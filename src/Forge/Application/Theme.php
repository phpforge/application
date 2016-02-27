<?php

namespace Forge\Application;

/**
 * Theme
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 */
class Theme {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * Set name
	 *
	 * @param string $name Name
	 *
	 * @return \Forge\Application\Theme
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @var string
	 */
	protected $dir;

	/**
	 * Set directory
	 *
	 * @param string $dir Directory
	 *
	 * @return \Forge\Application\Theme
	 */
	public function setDir($dir) {
		$this->dir = $dir;
		return $this;
	}

	/**
	 * Get directory
	 *
	 * @return string
	 */
	public function getDir() {
		return $this->dir;
	}

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * Set URL
	 *
	 * @param string $url URL
	 *
	 * @return \Forge\Application\Theme
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * Get URL
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @var string
	 */
	protected $layout;

	/**
	 * Set layout
	 *
	 * @param string $layout Layout
	 *
	 * @return \Forge\Application\Theme
	 */
	public function setLayout($layout) {
		$this->layout = $layout;
		return $this;
	}

	/**
	 * Get layout
	 *
	 * @return string
	 */
	public function getLayout() {
		return $this->layout;
	}
}