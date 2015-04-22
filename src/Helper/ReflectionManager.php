<?php
namespace GMO\Beanstalk\Helper;

use GMO\Common\ClassNameResolverInterface;
use GMO\Common\Collections\ArrayCollection;
use PHPParser\Error;
use PhpParser\Lexer;
use PHPParser\Node\Stmt\Namespace_ as NamespaceNode;
use PHPParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Parser;

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
		$parser = new Parser(new Lexer());
		$phpCode = file_get_contents($file);
		try {
			$stmts = $parser->parse($phpCode);
		} catch (Error $e) {
			return false;
		}
		foreach ($stmts as $stmt) {
			if ($stmt instanceof NamespaceNode) {
				$namespace = implode("\\", $stmt->name->parts);
				foreach($stmt->stmts as $subStmt) {
					if ($subStmt instanceof ClassNode) {
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
