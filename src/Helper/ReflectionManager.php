<?php
namespace GMO\Beanstalk\Helper;

use GMO\Common\ClassNameResolverInterface;
use GMO\Common\Collections\ArrayCollection;

class ReflectionManager implements ClassNameResolverInterface {

	/**
	 * @param string $file file path
	 * @return \ReflectionClass
	 */
	public static function getClass($file) {
		$file = (string) $file;
		if (!static::$classes) {
			static::$classes = new ArrayCollection();
		}

		if (!static::$classes->get($file)) {
			$name = ReflectionManager::getClassName($file);
			static::$classes->set($file, new \ReflectionClass($name));
		}

		return static::$classes->get($file);
	}

	private static function getClassName($file) {
		$parser = new NamespaceParser();
		$phpCode = file_get_contents($file);
		return $parser->parse($phpCode);
	}

	public static function className() { return get_called_class(); }

	/** @var ArrayCollection */
	private static $classes;
}
