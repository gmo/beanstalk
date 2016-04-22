<?php
namespace GMO\Beanstalk\Helper;

use GMO\Common\Str;
use Symfony\Component\Finder\Finder;

/**
 * ClassFinder extends Symfony Finder to find classes within a PSR-0 namespace.
 * @package Service\Finder
 */
class ClassFinder implements \IteratorAggregate, \Countable {

	/**
	 * Adds rules that classes must match.
	 *
	 * You can use globs or simple strings.
	 *
	 * $finder->path('Some\*\Namespace')
	 *
	 * @param string $pattern A pattern (a glob or a string)
	 * @return $this
	 * @see Symfony\Component\Finder\Finder::path
	 */
	public function inNamespace($pattern) {
		$pattern = $this->convertNamespaceToPattern($pattern);
		$this->finder->path($pattern);
		return $this;
	}

	/**
	 * Adds rules that classes must not match.
	 *
	 * You can use globs or simple strings.
	 *
	 * $finder->path('Some\*\Namespace')
	 *
	 * @param string $pattern A pattern (a glob or a string)
	 * @return $this
	 * @see Symfony\Component\Finder\Finder::notPath
	 */
	public function notNamespace($pattern) {
		$pattern = $this->convertNamespaceToPattern($pattern);
		$this->finder->notPath($pattern);
		return $this;
	}

	/**
	 * Adds rules that classes must match.
	 *
	 * You can use patterns (delimited with / sign), globs or simple strings.
	 *
	 * $finder->name('*')
	 * $finder->name('Abstract*')
	 * $finder->name('/^Abstract/') // same as above
	 * $finder->name('TestClass')
	 *
	 * @param string $pattern A pattern (a regexp, a glob, or a string)
	 * @return $this
	 * @see Symfony\Component\Finder\Finder::name
	 */
	public function name($pattern) {
		$pattern = $this->addFileExtensionToPattern($pattern);
		$this->finder->name($pattern);
		$this->useDefaultName = false;
		return $this;
	}

	/**
	 * Adds rules that classes must not match.
	 *
	 * You can use patterns (delimited with / sign), globs or simple strings.
	 *
	 * $finder->name('*')
	 * $finder->name('Abstract*')
	 * $finder->name('/^Abstract/') // same as above
	 * $finder->name('TestClass')
	 *
	 * @param string $pattern A pattern (a regexp, a glob, or a string)
	 * @return $this
	 * @see Symfony\Component\Finder\Finder::notName
	 */
	public function notName($pattern) {
		$pattern = $this->addFileExtensionToPattern($pattern);
		$this->finder->notName($pattern);
		return $this;
	}

	/**
	 * Adds tests for the directory depth.
	 *
	 * Usage:
	 *
	 *   $finder->depth('> 1') // the Finder will start matching at level 1.
	 *
	 *   $finder->depth('< 3') // the Finder will descend at most 3 levels of directories below the starting point.
	 *
	 * @param int $level The depth level expression
	 * @return $this
	 * @see Symfony\Component\Finder\Finder::depth
	 */
	public function depth($level) {
		$this->finder->depth($level);
		return $this;
	}

	public function map($closure) {
		$this->maps[] = $closure;
		return $this;
	}

	/**
	 * Add a class that classes must inherit from.
	 *
	 * @param string $class the fully qualified class name
	 * @return $this
	 */
	public function isSubclassOf($class) {
		$this->finder->filter(function (\SplFileInfo $file) use ($class) {
			return ReflectionManager::getClass($file)->isSubclassOf($class);
		});
		return $this;
	}

	/**
	 * Classes must be instantiable.
	 *
	 * @return $this
	 */
	public function isInstantiable() {
		$this->finder->filter(function (\SplFileInfo $file) {
			return ReflectionManager::getClass($file)->isInstantiable();
		});
		return $this;
	}

	/**
	 * Classes must be abstract.
	 *
	 * @return $this
	 */
	public function isAbstract() {
		$this->finder->filter(function (\SplFileInfo $file) {
			return ReflectionManager::getClass($file)->isAbstract();
		});
		return $this;
	}

	/**
	 * Classes must be interfaces.
	 *
	 * @return $this
	 */
	public function isInterface() {
		$this->finder->filter(function (\SplFileInfo $file) {
			return ReflectionManager::getClass($file)->isInterface();
		});
		return $this;
	}

	/**
	 * Returns an Iterator for the current ClassFinder configuration.
	 *
	 * @return \Iterator An iterator
	 */
	public function getIterator() {
		if ($this->useDefaultName && !$this->isDefaultSet) {
			$this->finder->name('*.php');
			$this->isDefaultSet = true;
		}
		$it = $this->finder->getIterator();
		$it = new MapIteratorWrapper($it, array(ReflectionManager::className(), 'getClass'));
		foreach ($this->maps as $map) {
			$it = new MapIteratorWrapper($it, $map);
		}
		return $it;
	}

	/**
	 * Counts all the results collected by the iterators.
	 *
	 * @return int
	 */
	public function count() {
		return iterator_count($this->getIterator());
	}

	/**
	 * Returns the classes for the current ClassFinder configuration
	 * @return \ReflectionClass[]
	 */
	public function getArray() {
		return iterator_to_array($this->getIterator());
	}

	private function addFileExtensionToPattern($pattern) {
		if (Str::startsWith($pattern, '/')) {
			$pos = strrpos($pattern, '$');
			if ($pos === false) {
				return $pattern;
			}
			list ($str1, $str2) = $this->splitStringAt($pattern, $pos);
			$pattern = $str1 . '\.php$' . $str2;
		} else {
			$pattern .= '.php';
		}
		return $pattern;
	}

	private function convertNamespaceToPattern($pattern) {
		if (Str::startsWith($pattern, '/')) {
//			$pattern = substr($pattern, 1);
//			$pos = strrpos($pattern, '/');
//			list ($str1, $str2) = $this->splitStringAt($pattern, $pos);
//			$str2 = '/' . $str2;
//			$pattern = '/' . str_replace('\\', '\/', $str1) . $str2;
		} else {
			$pattern = str_replace('\\', '/', $pattern);
		}
		return $pattern;
	}

	private function splitStringAt($str, $pos) {
		return array(
			substr($str, 0, $pos),
			substr($str, $pos + 1) ?: '',
		);
	}

	public function __construct($in) {
		$this->finder = Finder::create()
			->files()
			->in($in);
	}

	public static function create($in) {
		return new static($in);
	}

	/** @var \Symfony\Component\Finder\Finder */
	private $finder;
	private $useDefaultName = true;
	private $isDefaultSet = false;
	private $maps = array();

}
