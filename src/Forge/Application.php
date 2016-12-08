<?php

namespace Forge;

use Forge\Http;
use Forge\Application\Exception;
use Forge\Application\Base;
use Forge\Application\Request;
use Forge\Application\Theme;
use Forge\Application\View;
use Forge\Application\Menu;
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

		$this->buildRoutes();
	}

	/**
	 * Directory Class Loader
	 *
	 * @param type $loader   Class Loader
	 * @param type $classDir Class Directory to Search
	 */
	private function directoryClassLoader(&$loader, $classDir) {
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
		$request = $this->buildRequest($requestUri, $requestUri, $requestMethod);
		if ($request instanceof Request) {
			return $this->build($request);
		} else {
			throw new Exception('Unable to build request', Http::STATUS_CODE_404);
		}
	}

	/**
	 * Build request
	 *
	 * @param string  $originalUri  Original URI
	 * @param string  $requestUri   Request URI
	 * @param string $requestMethod Method
	 *
	 * @return mixed
	 */
	private function buildRequest($originalUri, $requestUri, $requestMethod) {
		$uri = preg_replace(array('#^'.BASE_URI.'#', '/\?.*$/', '/[\/]?$/'), '', $requestUri);
		foreach ($this->getModules() as $module) {
			foreach ($module->getRoutes() as $route) {
				if ($route->getRequestMethod() === $requestMethod) {
					foreach ($route->getUrls() as $url) {
						$checkUri = strtolower(!empty($uri) ? $uri : '/');
						if (preg_match('#^'.$url.'$#i', $checkUri, $matches) && $checkUri !== '/' || strtolower($url) === $checkUri) {

							$request = new Request();
							$request->setRoute($route)
								->setRouteUrl($url)
								->setUrl($originalUri)
								->setTheme(self::getTheme());

							// If we matched a regular expression set named groups
							if ($matches) {
								foreach ($matches as $key => $value) {
									if (is_string($key)) {
										$request->setParam($key, $value);
									}
								}
							}

							$exp = '/(\/?[^\/]*){1,2}/';
							if (preg_match_all($exp, preg_replace('/.*?'  . str_replace('/', '\/', $url) .  '(.*)$/', '$1', $originalUri), $matches)) {
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

		$nextUri = preg_replace('/\/[^\/]+$/', '', $requestUri);
		if (!empty($nextUri)) {
			return $this->buildRequest($originalUri, $nextUri, $requestMethod);
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
		$route = $request->getRoute();
		$class = $route->getClass();
		if (!class_exists($class)) {
			$render = new Exception('Module ' . strtolower($class) . ' does not exist', Http::STATUS_CODE_404);
		} else {
			$module = null;
			$requestClass = $route->getClass();
			foreach ($this->getModules() as $module) {
				if ($requestClass === get_class($module)) {
					if (method_exists($module, '__construct')) {
						$module->__construct();
					}

					/**
					 * Check ACL
					 */
					if (!$this->hasAccess($request)) {
						throw new \Exception('Access denied');
					}

					$this->setModule($module);
					$method = $route->getMethod();

					if ($route->getType() === 'template') {
						$view = new View();
						$view->setTemplate($route->getMethod());
						$params = $request->getParams();
						if (!empty($params)) {
							foreach ($params as $key => $value) {
								$view->$key = $value;
							}
						}
						$this->setContent($view->render());
					} else if (!method_exists($this->getModule(), $method)) {
						// Should never happen since if we find a module we default to /
						throw new Exception('Module ' . strtolower($class) . ' does not have method ' . $method, Http::STATUS_CODE_404);
					} else {
						$result = $module->$method();
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

	public function buildRoutes() {
		$routing = array('GET' => array());
		foreach ($this->getModules() as $mod) {
			// Get routing from menus
			if (method_exists($mod, 'menus')) {
				$menus = array();
				foreach ($mod->menus() as $name => $menu) {
					foreach ($menu as $key => $value) {
						$routing['GET'] = array_merge_recursive($value->getRoutesRecursive(), $routing['GET']);
						if ($value instanceof Menu) {
							$menus[$name][$key] = $value->toArray();
						}
					}
				}
				$this->menus = array_replace_recursive($menus, $this->menus);
			}

			// Get additional routing
			if (method_exists($mod, 'routes')) {
				foreach ($mod->routes() as $method => $route) {
					foreach ($route as $key => $value) {
						if (key_exists(strtoupper($method), $routing) && key_exists($value, $routing[strtoupper($method)])) {
							$routing[strtoupper($method)][$value] = array_merge_recursive($routing[strtoupper($method)][$value], array($key));
						} else {
							$routing[strtoupper($method)][$value] = array($key);
						}
					}
				}
			}

			self::callGlobalEvent($mod, 'bootstrap');
		}

		// Add extra routes to module routing
		foreach ($routing as $method => $routes) {
			foreach ($routes as $action => $route) {
				if (!empty($route)) {
					foreach ($this->getModules() as $module) {
						foreach ($module->getRoutes() as &$urls) {
							if ($urls->getRequestMethod() === $method) {
								if (in_array($action, $urls->getUrls())) {
									$urls->setUrls(array_merge($urls->getUrls(), $route));
								}
							}
						}
					}
				}
			}
		}
	}

	public function hasAccess(Request $request) {
		$acls = $request->getRoute()->getAcls();
		$roles = self::getRoles();
		if (!empty($acls)) {
			foreach ($acls as $acl) {
				if (in_array($acl, $roles)) {
					return true;
				}
			}
			return false;
		}
		return true;
	}

	public function validateMenu($type, $items) {
		foreach ($items as $name => &$menu) {

			if (!empty($menu['children'])) {
				$menu['children'] = $this->validateMenu($name, $menu['children']);
			}

			$uri = $menu['uri'];
			if ($uri !== '/' && $uri !== '#') {
				$request = $this->buildRequest($uri, $uri, 'GET');
				$access = $this->hasAccess($request);
				if (!$access) {
					unset($items[$name]);
				}
			} if (empty($menu['children']) && $uri === '#') {
				unset($items[$name]);
			}
		}
		return $items;
	}

	/**
	 * Render
	 *
	 * @throws Exception
	 */
	public function render() {
		$content = '';
		$this->callEvent($this->getModule(), 'preRender');
		$theme = self::getTheme();

		if ($this->isThemeEnabled() && $theme instanceof Theme) {
			$layout = new View();
			$layout->setTemplate($theme->getLayout());
			$layout->config = $this->getConfig();
			$layout->request = $this->getRequest();

			$menus = array();
			foreach ($this->menus as $type => &$menu) {
				$menu = $this->validateMenu($type, $menu);
			}

			$layout->menus = $this->menus;
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