<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Helper;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

/**
 * This wraps PHP Parser logic find the first fully qualified class name.
 */
class NamespaceParser
{
    private $parser;
    private $traverser;
    private $classNameVisitor;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new NameResolver());
        $this->classNameVisitor = new NamespacedClassNameVisitor();
        $this->traverser->addVisitor($this->classNameVisitor);
    }

    public function parse($code)
    {
        try {
            $stmts = $this->parser->parse($code);
            $this->traverser->traverse($stmts);
        } catch (Error $e) {
            return null;
        }

        return $this->classNameVisitor->getClassName();
    }
}
