<?php

namespace Gmo\Beanstalk\Helper;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor that stores the fully qualified class name for the first class visited and stops traversal.
 *
 * @internal
 */
final class NamespacedClassNameVisitor extends NodeVisitorAbstract
{
    private $className;

    public function getClassName(): ?string
    {
        return $this->className;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->className = null;
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof ClassNode) {
            return null;
        }

        $this->className = (string) $node->namespacedName;

        return NodeTraverser::STOP_TRAVERSAL;
    }
}
