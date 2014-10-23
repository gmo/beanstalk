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
		$parser = new \PHPParser_Parser(new \PHPParser_Lexer());
		$phpCode = file_get_contents($file);
		try {
			$stmts = $parser->parse($phpCode);
		} catch (\PHPParser_Error $e) {
			return false;
		}
		foreach ($stmts as $stmt) {
			if ($stmt instanceof \PHPParser_Node_Stmt_Namespace) {
				$namespace = implode("\\", $stmt->name->parts);
				foreach($stmt->stmts as $subStmt) {
					if ($subStmt instanceof \PHPParser_Node_Stmt_Class) {
						return $namespace . "\\" . $subStmt->name;
					}
				}
			}
		}
		return false;
	}

	public static function className() { return get_called_class(); }

	/** @var ArrayCollection */
	private static $classes;
}
