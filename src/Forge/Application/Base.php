<?php

namespace Forge\Application;

use Forge\Application\Exception;
use Forge\Http;

/**
 * Application base class
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 * @abstract
 */
abstract class Base {

	/**
	 * @var string
	 */
	protected static $config;

	/**
	 * @var array
	 */
	protected $menus = array();

	/**
	 * Get config
	 *
	 * @return string
	 */
	public static function getConfig() {
		if (!isset(self::$config)) {
			$config = include self::getAppDir() . DS . 'configs' . DS . 'application.php';
			self::$config = json_decode(json_encode($config));
		}
		return self::$config;
	}

	/**
	 * @var string
	 */
	protected static $appdir;

	/**
	 * Get application directory
	 *
	 * @return string
	 */
	public static function getAppDir() {
		if (!self::$appdir) {
			self::$appdir = preg_replace(array('/(\\\vendor\\\.*)$/', '/(\/vendor\/.*)$/'), '', __DIR__);
		}
		return self::$appdir;
	}

	/**
	 * @var string
	 */
	protected static $moddir;

	/**
	 * Get module directory
	 *
	 * @return string
	 */
	public static function getModDir() {
		if (!self::$moddir) {
			self::$moddir = self::getAppDir() . DS . 'modules';
		}
		return self::$moddir;
	}


	/**
	 * @var string
	 */
	protected static $baseUri;

	/**
	 * Get application URI
	 *
	 * @return string
	 */
	public static function getBaseUri() {
		if (!self::$baseUri) {
			self::$baseUri = BASE_URI;
		}
		return self::$baseUri;
	}

	/**
	 * @var string
	 */
	protected static $appUrl;

	/**
	 * Get application URL
	 *
	 * @return string
	 */
	public static function getAppUrl() {
		if (!self::$appUrl) {
			$port = filter_input(INPUT_SERVER, 'SERVER_PORT');
			self::$appUrl = (($port == Http::STANDARD_HTTPS_PORT) ? 'https' : 'http') . '://' . filter_input(INPUT_SERVER, 'SERVER_NAME') . (($port == Http::STANDARD_HTTP_PORT || $port == Http::STANDARD_HTTPS_PORT) ? '' : ':' . $port) . self::getBaseUri();
		}
		return self::$appUrl;
	}

	/**
	 * Call global event
	 *
	 * @param string $module Module
	 * @param string $name   Function name
	 */
	protected static function callGlobalEvent($module, $name) {
		$method = ucfirst($name);
		if (method_exists($module, $method)) {
			call_user_func_array(array($module, $method), array_slice(func_get_args(), 2));
		}
	}

	/**
	 * Call hook function
	 *
	 * @param string $name Function name
	 *
	 * @return array
	 */
	public static function callArrayHook($name) {
		$results = array();
		$method = 'hook' . ucfirst($name);
		foreach (self::getModules() as $module) {
			if (method_exists($module, $method)) {
				$result = call_user_func_array(array($module, $method), array_slice(func_get_args(), 1));

				foreach ($result as &$value) {
					if (is_object($value)) {
						$value = $value->toArray();
					}
				}
				$results = array_replace_recursive($result, $results);
			}
		}
		return $results;
	}

	/**
	 * Call hook function
	 *
	 * @param string $name Function name
	 *
	 * @return array
	 */
	public static function callHook($name) {
		$results = array();
		$method = 'hook' . ucfirst($name);
		foreach (self::getModules() as $module) {
			if (method_exists($module, $method)) {
				$results = array_replace_recursive(call_user_func_array(array($module, $method), array_slice(func_get_args(), 1)), $results);
			}
		}
		return $results;
	}

	/**
	 * @var string
	 */
	public static $defaultModule = 'main';

	/**
	 * Set default module
	 *
	 * @param string $name Module name
	 */
	public static function setDefaultModule($name) {
		self::$defaultModule = $name;
	}

	/**
	 * Get session
	 *
	 * @param string $key Session key
	 *
	 * @return mixed
	 */
	public static function getSession($key) {
		if (!isset($_SESSION[$key])) {
			$_SESSION[$key] = null;
		}
		return $_SESSION[$key];
	}

	/**
	 * Set session
	 *
	 * @param string $key   Key
	 * @param mixed  $value Value
	 */
	public static function setSession($key, $value) {
		$_SESSION[$key] = $value;
	}

	/**
	 * @var string
	 */
	protected $module;

	/**
	 * Set module
	 *
	 * @param \Forge\Application\Module $module Module
	 *
	 * @return \Forge\Application\Base
	 */
	public function setModule($module) {
		$this->module = $module;
		return $this;
	}

	/**
	 * Get module
	 *
	 * @return \Forge\Application\Module
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * @var array
	 */
	private static $modules;

	/**
	 * Get modules
	 *
	 * @return array
	 */
	public static function getModules() {
		return self::$modules;
	}

	/**
	 * Set modules
	 *
	 * @param \Forge\Application\Module[] $modules Array of Module
	 *
	 * @return \Forge\Application\Base
	 */
	protected function setModules($modules) {
		self::$modules = $modules;
		return $this;
	}

	/**
	 * @var \Forge\Application\Theme
	 */
	protected static $theme;

	/**
	 * Get theme
	 *
	 * @return \Forge\Application\Theme
	 */
	public static function getTheme() {
		if (empty(self::$theme)) {
			self::setTheme('default');
		}
		return self::$theme;
	}

	/**
	 * Set theme
	 *
	 * @param string $name Theme name
	 *
	 * @throws Exception
	 */
	public static function setTheme($name) {
		if (file_exists(self::getAppDir() . DS . 'themes' . DS . $name . DS . 'layout.phtml')) {
			self::$theme = new Theme();
			self::$theme->setName($name)
				->setDir(self::getAppDir() . DS . 'themes' . DS . $name)
				->setUrl(self::getAppUrl() . '/themes/' . $name)
				->setLayout(self::getAppDir() . DS . 'themes' . DS . $name . DS . 'layout.phtml');
		} else {
			throw new Exception('Theme dir doesn\'t exist: ' . self::getAppDir() . DS . 'themes' . DS . $name . DS . 'layout.phtml');
		}
	}

	/**
	 * @var boolean
	 */
	protected static $enableTheme = true;

	/**
	 * Disable theme
	 *
	 * @return \Forge\Application\Base
	 */
	public function disableTheme() {
		self::$enableTheme = false;
		return $this;
	}

	/**
	 * Is theme enabled
	 *
	 * @return boolean
	 */
	public function isThemeEnabled() {
		return self::$enableTheme;
	}

	/**
	 * @var \Forge\Application\Request
	 */
	protected static $request;

	/**
	 * Set request
	 *
	 * @param \Forge\Application\Request $request Request
	 *
	 * @return \Forge\Application\Base
	 */
	protected function setRequest(Request $request) {
		self::$request = $request;
		return $this;
	}

	/**
	 * Get request
	 *
	 * @return \Forge\Application\Request
	 */
	public static function getRequest() {
		return self::$request;
	}

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * Set content
	 *
	 * @param string $content Content
	 *
	 * @return \Forge\Application\Base
	 */
	protected function setContent($content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * Get content
	 *
	 * @return string
	 */
	protected function getContent() {
		return $this->content;
	}

	/**
	 * @var array
	 */
	protected static $global;

	/**
	 * Get Global
	 *
	 * @param string $key Key
	 *
	 * @return mixed
	 */
	public static function getGlobal($key) {
		if (isset(self::$global[$key])) {
			return self::$global[$key];
		}
		return false;
	}

	/**
	 * Get Globals
	 *
	 * @return array
	 */
	public static function getGlobals() {
		return self::$global;
	}

	/**
	 * Set Global
	 *
	 * @param string $key   Key
	 * @param mixed  $value Value
	 *
	 * @throws Exception
	 */
	public static function setGlobal($key, $value) {
		if (isset(self::$global[$key])) {
			throw new Exception('Application global ' . $key . ' has already been specified');
		}
		self::$global[$key] = $value;
	}
}