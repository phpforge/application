<?php

namespace Forge\Application;

use Forge\Application\Route;
use Forge\Application\Theme;

/**
 * Request model
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 */
class Request {

	/**
	 * @var \Forge\Application\Route
	 */
	protected $route;

	/**
	 * Get route
	 *
	 * @return \Forge\Application\Route
	 */
	public function getRoute()
	{
		return $this->route;
	}

	/**
	 * Set route
	 *
	 * @param \Forge\Application\Route $value Route
	 *
	 * @return \Forge\Application\Request
	 */
	public function setRoute(Route $value)
	{
		$this->route = $value;
		return $this;
	}

	/**
	 * @var \Forge\Application\Theme
	 */
	protected $theme;

	/**
	 * Get theme
	 *
	 * @return \Forge\Application\Theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * Set theme
	 *
	 * @param \Forge\Application\Theme $value Theme
	 *
	 * @return \Forge\Application\Request
	 */
	public function setTheme(Theme $value)
	{
		$this->theme = $value;
		return $this;
	}

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * Set url
	 *
	 * @param string $url URL
	 *
	 * @return \Forge\Application\Request
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * Get url
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Get route url
	 *
	 * @var string
	 */
	protected $routeUrl;

	/**
	 * Set route url
	 *
	 * @param string $url Route URL
	 *
	 * @return \Forge\Application\Request
	 */
	public function setRouteUrl($url) {
		$this->routeUrl = $url;
		return $this;
	}

	/**
	 * Get route url
	 *
	 * @return string
	 */
	public function getRouteUrl() {
		return $this->routeUrl;
	}

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * Get params
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Get param
	 *
	 * @param string $name Name
	 *
	 * @return mixed
	 */
	public function getParam($name)
	{
		if (isset($this->params[strtolower($name)]))
		{
			return $this->params[strtolower($name)];
		}
		return false;
	}

	/**
	 * Set param
	 *
	 * @param string $name Name
	 * @param mixed  $value Value
	 *
	 * @return \Forge\Application\Request
	 */
	public function setParam($name, $value)
	{
		$this->params[strtolower($name)] = $value;
		return $this;
	}
}