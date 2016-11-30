<?php

namespace Forge\Application;

use Forge\Http;
use Forge\Application;
use Forge\Application\Exception;
use Forge\Application\View;

/**
 * Module
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 * @abstract
 */
abstract class Module extends View {

	/**
	 * Set default module
	 *
	 * @param string $name Module name
	 */
	public function setDefaultModule($name) {
		return Application::setDefaultModule($name);
	}

	/**
	 * Set theme
	 *
	 * @param string $name Theme name
	 *
	 * @throws Exception
	 */
	public function setTheme($theme) {
		return Application::setTheme($theme);
	}

	/**
	 * Set roles
	 *
	 * @param string $roles Roles
	 */
	public function setRoles($roles) {
		return Application::setRoles($roles);
	}

	/**
	 * Add roles
	 *
	 * @param string $role Role
	 */
	public function addRole($role) {
		return Application::addRole($role);
	}

	/**
	 * Get config
	 *
	 * @return string
	 */
	public function getConfig() {
		return Application::getConfig();
	}

	/**
	 * Get request
	 *
	 * @return \Forge\Application\Request
	 */
	public function getRequest() {
		return Application::getRequest();
	}

	/**
	 * Set Global
	 *
	 * @param string $key   Key
	 * @param string $value Value
	 */
	public function setGlobal($key, $value) {
		Application::setGlobal($key, $value);
	}

	/**
	 * @var \Forge\Application\Route[]
	 */
	private $routes;

	/**
	 * Get routes
	 *
	 * @return \Forge\Application\Route[]
	 */
	public function getRoutes() {
		return $this->routes;
	}

	/**
	 * Set routes
	 *
	 * @param \Forge\Application\Route[] $routes Routes
	 *
	 * @return \Forge\Application\Module
	 */
	public function setRoutes($routes) {
		$this->routes = $routes;
		return $this;
	}

	/**
	 * Get template
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	public function getTemplate() {
		if (!$this->template) {
			$view = $this->getDir() . DS . 'Template';

			if (!file_exists($view)) {
				throw new Exception('View directory does not exist: ' . $view, Http::STATUS_CODE_404);
			}
			$this->template = $view . DS . preg_replace('/(post|get|put|delete)$/', '', strtolower($this->getRequest()->getRoute()->getMethod())) . '.' . strtolower(filter_input(INPUT_SERVER, 'REQUEST_METHOD'));
		}
		return $this->template;
	}

	/**
	 * @var string
	 */
	private $dir = null;

	/**
	 * Get directory
	 *
	 * @return string
	 */
	public function getDir() {
		if (!$this->dir) {
			$rc = $this->getReflectionClass();
			$this->dir = dirname($rc->getFileName()) . DS . $rc->getShortName();
		}
		return $this->dir;
	}

	/**
	 * @var \ReflectionClass
	 */
	private $rc;

	/**
	 * get reflection class
	 *
	 * @return \ReflectionClass
	 */
	public function getReflectionClass() {
		if (!$this->rc) {
			$this->rc = new \ReflectionClass(get_class($this));
		}
		return $this->rc;
	}

	/**
	 * Get file
	 *
	 * @return string
	 */
	public function getFile() {
		if (!$this->file) {
			$rc = $this->getReflectionClass();
			$this->file = $rc->getFileName();
		}
		return $this->file;
	}

	/**
	 * @var string
	 */
	private $name = null;

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		if (!$this->name) {
			$this->name = preg_replace('/(.*)Module$/', '$1', join('', array_slice(explode('\\', get_class($this)), -1)));
		}
		return $this->name;
	}

	/**
	 * Call Hook
	 *
	 * @param string $name hook Call
	 *
	 * @return mixed
	 */
	public function callHook() {
		return forward_static_call_array('Forge\Application::callHook', func_get_args());
	}
}