<?php

namespace Forge\Application\Module;

use Composer\Autoload\ClassLoader;
use Forge\Application\Route;
use Forge\Application\Base;

/**
 * Module Loader
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.org>
 * @copyright  1999-2015 Devforge Inc
 */
class Loader extends Base {

	/**
	 * Loader
	 *
	 * @param ClassLoader $loader Loader
	 *
	 * @return array
	 */
	public static function load(ClassLoader $loader) {
		$modules = array();
		$classmap = $loader->getClassMap();
		foreach ($classmap as $class => $file) {

			// The following is required as class_exists, is_subclass_of and ReflectionClass will throw a fatal error if class extends a non-existent class
			// @todo allow custom namespaces
			if (!preg_match('/^main|phpforge|module/i', $class) && !preg_match('/^' . str_replace('/', '\/', self::getModDir()) . '/i', $file)) {
				continue;
			}

			if (class_exists($class)) {
				if (is_subclass_of($class, 'Forge\Application\Module')) {
					$ref = new \ReflectionClass($class);
					if ($ref->IsInstantiable()) {
						$mod = $ref->newInstanceWithoutConstructor();
						$acls = array();
						if (method_exists($mod, 'acl')) {
							$acls = $mod->acl();
						}

						$routemap = array();
						$methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
						foreach($methods as $method) {
							if (preg_match('/Post|Get|Put|Delete$/', $method->name)) {
								$methodName = preg_replace('/Post|Get|Put|Delete$/', '', $method->name);
								$methodType = strtoupper(preg_replace('/.*(Post|Get|Put|Delete)$/', '$1', $method->name));

								$urls = array();
								if (strtolower($methodName) == strtolower($ref->getShortName())) {
									if (strtolower($method->class) == strtolower(self::$defaultModule) && $methodType == 'GET') {
										$urls[] =  '/';
									}
									$urls[] =  '/' . strtolower(preg_replace('/\\\/', '/', $class));
								} else {
									$urls[] =  '/' . strtolower(preg_replace('/\\\/', '/', $class) . '/' . $methodName);
								}

								$route = new Route();
								$route->setClass($class)
									->setMethod($method->name)
									->setRequestMethod($methodType)
									->setUrls($urls);

								if (key_exists($method->name, $acls)) {
									$route->setAcls($acls[$method->name]);
								}

								$routemap[] = $route;
							}
						}

						$mod->setRoutes($routemap);
						$modules[] = $mod;
					}
				}
			}
		}

		return $modules;
	}
}