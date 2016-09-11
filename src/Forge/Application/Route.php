<?php

namespace Forge\Application;

use Forge\Application\Module;

/**
 * Route model
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 */
class Route {

	/**
	 * @var string
	 */
	protected $class;

	/*
	 * Set class
	 *
	 * @param string $class Class
	 *
	 * @return self
	 */
	public function setClass($class) {
		$this->class = $class;
		return $this;
	}

	/*
	 * Get class
	 *
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * @var string
	 */
	protected $method;

	/*
	 * Set method
	 *
	 * @return thing
	 */
	public function setMethod($method) {
		$this->method = $method;
		return $this;
	}

	/*
	 * Get method
	 *
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @var string
	 */
	protected $requestMethod;

	/*
	 * Set request method
	 *
	 * @return thing
	 */
	public function setRequestMethod($requestMethod) {
		$this->requestMethod = $requestMethod;
		return $this;
	}

	/*
	 * Get request method
	 *
	 * @return string
	 */
	public function getRequestMethod() {
		return $this->requestMethod;
	}

	/**
	 * @var array
	 */
	protected $urls = array();

	/*
	 * Set Urls
	 *
	 * @param array $urls URLs
	 *
	 * @return self
	 */
	public function setUrls($urls) {
		$this->urls = $urls;
		return $this;
	}

	/*
	 * Get Urls
	 *
	 * @return array Array of URL's
	 */
	public function getUrls() {
		return $this->urls;
	}

	/*
	 * Add Url
	 *
	 * @param string $url
	 */
	public function addUrl($url) {
		$this->urls[] = $url;
	}

	/**
	 * @var Forge\Application\Module
	 */
	protected $module;

	/*
	 * Get module
	 *
	 * @return Forge\Application\Module
	 */
	public function getModule() {
		return $this->module;
	}

	/*
	 * Set module
	 *
	 * @return Forge\Application\Module
	 * @return self
	 */
	public function setModule(Module $value) {
		$this->module = $value;
		return $this;
	}
}