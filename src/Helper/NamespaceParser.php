<?php

declare(strict_types=1);

namespace Gmo\Beanstalk\Helper;

use PHPParser\Error;
use PhpParser\Lexer;
use PHPParser\Node\Stmt\Class_ as ClassNode;
use PHPParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Parser;

/**
 * This wraps PHP Parser logic to account for both 0.9.x and 1.x versions
 */
class NamespaceParser
{
    private $oldVersion;
    private $parser;

    public function __construct()
    {
        $this->oldVersion = !class_exists('\PhpParser\Parser');
        if ($this->oldVersion) {
            $this->parser = new \PHPParser_Parser(new \PHPParser_Lexer());
        } else {
            $this->parser = new Parser(new Lexer());
        }
    }

    public function parse($code)
    {
        if ($this->oldVersion) {
            return $this->parseOldVersion($code);
        }

        return $this->parseNewVersion($code);
    }

    private function parseOldVersion($code)
    {
        try {
            $stmts = $this->parser->parse($code);
        } catch (\PHPParser_Error $e) {
            return false;
        }
        foreach ($stmts as $stmt) {
            if ($stmt instanceof \PHPParser_Node_Stmt_Namespace) {
                $namespace = implode("\\", $stmt->name->parts);
                foreach ($stmt->stmts as $subStmt) {
                    if ($subStmt instanceof \PHPParser_Node_Stmt_Class) {
                        return $namespace . "\\" . $subStmt->name;
                    }
                }
            }
        }

        return false;
    }

    private function parseNewVersion($code)
    {
        try {
            $stmts = $this->parser->parse($code);
        } catch (Error $e) {
            return false;
        }
        foreach ($stmts as $stmt) {
            if ($stmt instanceof NamespaceNode) {
                $namespace = implode("\\", $stmt->name->parts);
                foreach ($stmt->stmts as $subStmt) {
                    if ($subStmt instanceof ClassNode) {
                        return $namespace . "\\" . $subStmt->name;
                    }
                }
            }
        }

        return false;
    }
}
