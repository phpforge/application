<?php

namespace Forge\Application;

class Menu {

	protected $title;

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	protected $uri;

	public function getUri() {
		if (!empty($this->routes)) {
			return $this->routes[0];
		}
		
		return $this->uri;
	}

	public function setUri($uri) {
		$this->uri = $uri;
		return $this;
	}

	protected $priority;

	public function getPriority() {
		return $this->priority;
	}

	public function setPriority($priority) {
		$this->priority = $priority;
		return $this;
	}

	protected $group;

	public function getGroup() {
		return $this->group;
	}

	public function setGroup($group) {
		$this->group = $group;
		return $this;
	}

	protected $children;

	public function getChildren() {
		return $this->children;
	}

	public function setChildren($children) {
		$this->children = $children;
		return $this;
	}

	public function hasChildren() {
		if (!empty($this->children)) {
			return true;
		}
		return false;
	}

	protected $route;

	public function getRoute() {
		return $this->route;
	}

	public function setRoute($route) {
		$this->route = $route;
		return $this;
	}

	protected $class;

	public function getClass() {
		return $this->class;
	}

	public function setClass($class) {
		$this->class = $class;
		return $this;
	}

	public function __construct($title, $children = array(), $uri = '#', $route = '', $class='', $priority = 0, $group = 'default') {
		$this->setTitle($title)
			->setUri($uri)
			->setRoute($route)
			->setClass($class)
			->setPriority($priority)
			->setGroup($group)
			->setChildren($children);
	}

	public function getRoutesRecursive() {
		$routes = array();
		if ($this->hasChildren()) {
			$children = $this->getChildren();
			foreach ($children as $child) {
				$routes = array_replace_recursive($child->getRoutesRecursive(), $routes);
			}
		}

		$route = $this->getRoute();
		if (!empty($route)) {
			$routes = array_replace_recursive(array($route => array(preg_replace('#^'.BASE_URI.'#', '', $this->getUri()))), $routes);			
		}
		return $routes;
	}

	/**
	 * to Array
	 *
	 * @return array
	 */
	public function toArray() {
		$results = array();
		foreach ($this as $key => $value) {
			if ($key === 'children') {
				$children = array();
				foreach ($value as $ckey => $cvalue) {
					$children[$ckey] = $cvalue->toArray();
				}
				$results[$key] = $children;
			} else {
				$results[$key] = $value;
			}
		}
		return $results;
	}
}