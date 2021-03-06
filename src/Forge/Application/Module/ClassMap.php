<?php

namespace Forge\Application\Module;

use Forge\Application\View;
use Forge\Application\Exception;

/**
 * ClassMap
 *
 * @package    Forge
 * @subpackage Application
 * @author     Benjamin C. Tehan <benjamin.tehan@devforge.io>
 * @copyright  1999-2015 Devforge Inc
 */
class ClassMap  {

	const COMPOSER = 'composer.json';
	const TEMPLATE = 'autoload_classmap.phtml';
	const CLASSMAP = 'autoload_classmap.php';
	const ISPHP = '/\.php$/';

	/**
	 * inherit
	 *
	 * @param string $dir Directory
	 *
	 * @throws \Forge\Application\Exception
	 * @return array
	 */
	public function inherit($dir) {
		$classmap = array();
		$composer = $dir . DS . self::COMPOSER;
		$autoloadClassmap = $dir . DS . self::CLASSMAP;

		if (file_exists($autoloadClassmap)) {
			$content = include $autoloadClassmap;
			$classmap = array_merge($classmap, $content);
		} else if (file_exists($composer)) {
			set_include_path(get_include_path() . PATH_SEPARATOR . $dir);
			$json = json_decode(file_get_contents($composer));
			if (isset($json->autoload)) {
				if (isset($json->autoload->{'psr-0'})) {
					$psr = $json->autoload->{'psr-0'};
					$key = key($psr);
					if (isset($psr->$key)) {
						$src = $dir . DS . $psr->$key;
						$srcFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src));
						$phpFiles = new \RegexIterator($srcFiles, self::ISPHP);
						foreach ($phpFiles as $phpFile) {
							$class = preg_replace(array(self::ISPHP, '/\//'), array('', '\\'), str_replace($src, '', $phpFile->getPathName()));
							if (!preg_match('/^' . $key . '/', $class)) {
								throw new Exception('Module ' . $key . ' should have the same namespace however it\'s declared as ' . $class);
							}
							$classmap[$class] = str_replace($dir, '', $phpFile->getPathName());
						}
					}
				} if (isset($json->autoload->{'psr-4'})) {
					// @todo add psr-4
				}
			}

			$view = new View();
			$view->setTemplate(__DIR__ . DS . self::TEMPLATE);
			$view->classmap = $classmap;
			$view->save($dir . DS . self::CLASSMAP);
		}

		return $classmap;
	}
}