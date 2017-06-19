<?php

namespace GMO\Beanstalk\Helper;

class ReflectionManager
{
    /** @var \ReflectionClass[] */
    private static $classes = [];

    /**
     * @param string $file file path
     *
     * @return \ReflectionClass
     */
    public static function getClass($file)
    {
        $file = (string) $file;

        if (!isset(static::$classes[$file])) {
            $name = ReflectionManager::getClassName($file);
            static::$classes[$file] = new \ReflectionClass($name);
        }

        return static::$classes[$file];
    }

    private static function getClassName($file)
    {
        $parser = new NamespaceParser();
        $phpCode = file_get_contents($file);

        return $parser->parse($phpCode);
    }

    public static function className()
    {
        return get_called_class();
    }
}
