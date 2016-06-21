<?php

namespace Forge\Application\Module;

use Composer\Autoload\ClassLoader;
use Forge\Application\Module;
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
		$mods = array();
		$classmap = $loader->getClassMap();

		foreach ($classmap as $class => $file) {
			if (preg_match('/^phpforge|forge|devforge|module/i', $class) || preg_match('/^' . str_replace('/', '\/', self::getModDir()) . '/i', $file)) {

				if (class_exists($class)) {
					$ref = new \ReflectionClass($class);
					if ($ref->IsInstantiable()) {
						$mod = $ref->newInstanceWithoutConstructor();
						if ($mod instanceof Module) {
							$routemap = array();
							$methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_FINAL);
							foreach($methods as $method) {
								if (preg_match('/Post|Get|Put|Delete$/', $method->name)) {
									$methodName = preg_replace('/Post|Get|Put|Delete$/', '', $method->name);
									$methodType = strtoupper(preg_replace('/.*(Post|Get|Put|Delete)$/', '$1', $method->name));
									if ($method->class == $class) {
										$urls = array();
										if (strtolower($methodName) == strtolower($ref->getShortName())) {
											if (strtolower($method->class) == strtolower(self::$defaultModule) && $methodType == 'GET') {
												$urls[] = '/';
											}
											$urls[] = '/' . strtolower(preg_replace('/\\\/', '/', $class));
										} else {
											$urls[] = '/' . strtolower(preg_replace('/\\\/', '/', $class) . '/' . $methodName);
										}

										if (!preg_match('/^(event|global|hook)/', $methodName)) {

											$route = new Route();
											$route->setClass($method->class)
												->setMethod($method->name)
												->setRequestMethod($methodType)
												->setUrls($urls);

											$routemap[] = $route;
										}
									}
								}
							}

							$mod->setRoutes($routemap);
							$modules[] = $mod;
						}
					}
				}
			}
		}
		return $modules;
	}
}