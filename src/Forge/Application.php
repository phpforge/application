<?php

namespace Forge;

use Forge\Http;
use Forge\Application\Exception;
use Forge\Application\Base;
use Forge\Application\Request;
use Forge\Application\Theme;
use Forge\Application\View;
use Forge\Application\Module\Loader;
use Forge\Application\Module\ClassMap;
use Composer\Autoload\ClassLoader;

/**
 * Application
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 */
class Application extends Base {

	/**
	 * Construct
	 *
	 * @param ClassLoader $loader Loader
	 *
	 * @throws Exception
	 */
	public function __construct(ClassLoader $loader) {

		if (empty($loader->getClassMap())) {
			throw new Exception('You are required to run: composer dump-autoload -o', Http::STATUS_CODE_404);
		}

		$this->directoryClassLoader($loader, self::getAppDir() . DS . 'modules');
		$this->setModules(Loader::load($loader));
		$this->directoryClassLoader($loader, self::getAppDir() . DS . 'libraries');

		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		foreach($this->getModules() as $module) {
			self::callGlobalEvent($module, 'bootstrap');
		}
	}

	/**
	 * Directory Class Loader
	 *
	 * @param type $loader   Class Loader
	 * @param type $classDir Class Directory to Search
	 */
	private function directoryClassLoader(&$loader, $classDir)
	{
		$classmap = new ClassMap();
		$dirs = glob($classDir . '/*', GLOB_ONLYDIR);
		if ($dirs) {
			foreach ($dirs as $dir) {
				$loader->addClassMap($classmap->inherit($dir));
			}
		}
	}

	/**
	 * Process
	 *
	 * @return self
	 */
	public function process() {
		return $this->direct(filter_input(INPUT_SERVER, 'REQUEST_URI'), filter_input(INPUT_SERVER, 'REQUEST_METHOD'));
	}

	/**
	 * Direct
	 *
	 * @param string $requestUri    URI
	 * @param string $requestMethod Method
	 *
	 * @return self
	 * @throws Exception
	 */
	public function direct($requestUri, $requestMethod) {
		$request = $this->buildRequest($requestUri, $requestMethod, $this->getConfig()->routing->depth);
		if ($request instanceof Request) {
			return $this->build($request);
		} else {
			throw new Exception('Unable to build request', Http::STATUS_CODE_404);
		}
	}

	/**
	 * Build request
	 *
	 * @param string  $requestUri    URL
	 * @param string  $requestMethod Method
	 * @param integer $depth         Depth
	 *
	 * @return mixed
	 */
	private function buildRequest($requestUri, $requestMethod, $depth = 3) {
		$full = preg_replace('/\?.*$/', '', $requestUri);
		if ($depth == 0) {
			// Probably throw error here if $full is not /
			$uri = $full;
		} else {
			$exp = '/(\/[^\/]*){1,' . $depth . '}/';
			$matches = array();
			if ($full == '/') {
				$uri = '/';
			} else if (preg_match($exp, $full, $matches)) {
				$uri = $matches[0];
			}
		}

		foreach ($this->getModules() as $module) {
			foreach ($module->getRoutes() as $route) {
				if ($route->getRequestMethod() == $requestMethod) {
					foreach ($route->getUrls() as $url) {
						if (strtolower($url) == strtolower($uri)) {

							$request = new Request();
							$request->setRoute($route)
								->setRouteUrl($url)
								->setUrl($full);

							$request->setTheme(self::getTheme());

							$exp = '/(\/?[^\/]*){1,2}/';
							if (preg_match_all($exp, preg_replace('/^' . str_replace('/', '\/', $url) . '/', '', $full), $matches)) {
								foreach ($matches[0] as $kvp) {
									list ($key, $value) = array_pad(explode('/', preg_replace('/^\//', '', $kvp), 2), 2, null);
									if (!empty($key)) {
										$request->setParam($key, $value);
									}
								}
							}

							return $request;
						}
					}
				}
			}
		}
		if ($depth > 0) {
			return $this->buildRequest($requestUri, $requestMethod, $depth - 1);
		}

		return null;
	}

	/**
	 * Build
	 *
	 * @param Request $request Request
	 *
	 * @return \Forge\Application
	 * @throws Exception
	 */
	public function build(Request $request) {

		$this->setRequest($request);
		$class = $this->getRequest()->getRoute()->getClass();
		if (!class_exists($class)) {
			$render = new Exception('Module ' . strtolower($class) . ' does not exist', Http::STATUS_CODE_404);
		} else {

			$module = null;
			$requestClass = $request->getRoute()->getClass();
			foreach($this->getModules() as $module) {
				if ($requestClass == get_class($module)) {
					if (method_exists($module, '__construct')) {
						$module->__construct();
					}
					$this->setModule($module);

					$this->callEvent($this->getModule(), 'init')
						->callEvent($this->getModule(), 'request', $this->getRequest())
						->callEvent($this->getModule(), 'route', $this->getRequest()->getRoute());

					$method = $this->getRequest()->getRoute()->getMethod();
					if (!method_exists($this->getModule(), $method)) {
						// Should never happen since if we find a module we default to /
						throw new Exception('Module ' . strtolower($class) . ' does not have method ' . $method, Http::STATUS_CODE_404);
					} else {
						$this->callEvent($this->getModule(), 'preAction');

						$result = $module->$method();
						$this->callEvent($this->getModule(), 'postAction');

						if ($result instanceof View) {
							$render = $result->render();
						} else if ($result) {
							$this->setContent($result)
								->disableTheme();
						} else if ($module instanceof View) {
							$template = $module->getTemplate();
							if ($template) {
								if (file_exists($template)) {
									$module->setTemplate($template);
									$render = $this->getModule()->render();
								}
							}
						}

						if (isset($render)) {
							$this->setContent($render);
						}
					}
					break;
				}
			}

		}

		return $this;
	}

	/**
	 * Call event
	 *
	 * @param \Forge\Application\Module $module Module
	 * @param string                    $name   Event name
	 *
	 * @return \Forge\Application
	 */
	private function callEvent($module, $name) {
		$method = 'event' . ucfirst($name);
		if (method_exists($module, $method)) {
			call_user_func_array(array($module, $method), array_slice(func_get_args(), 2));
		}
		return $this;
	}

	/**
	 * Render
	 *
	 * @throws Exception
	 */
	public function render() {

		$this->callEvent($this->getModule(), 'preRender');
		$theme = self::getTheme();
		$content = '';

		if ($this->isThemeEnabled() && $theme instanceof Theme) {

			$layout = new View();
			$layout->setTemplate($theme->getLayout());

			$layout->config = $this->getConfig();
			$layout->request = $this->getRequest();
			$layout->theme = $theme;
			$layout->application = $this;
			$layout->content = $this->getContent();

			$module = $this->getModule();
			foreach ($module as $key => $value) {
				if ($key == 'content') {
					$layout->content .= $value;
				} else if (isset($layout->$key)) {
					throw new Exception('View is using a reserved variable ' . $key);
				} else {
					$layout->$key = $value;
				}
			}

			$globals = self::getGlobals();
			if ($globals) {
				foreach($globals as $key => $value) {
					$layout->$key = $value;
				}
			}

			$content = $layout->render();
		} else {
			$content = $this->getContent();
		}

		echo $content;
		$this->callEvent($this->getModule(), 'postRender');
	}
}