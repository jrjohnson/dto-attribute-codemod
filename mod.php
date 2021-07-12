<?php

declare(strict_types=1);

use Codeshift\AbstractCodemod;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;


class DTOVisitor extends NodeVisitorAbstract
{
    public function afterTraverse(array $nodes)
    {
        foreach ($nodes as $node) {
            if ($node instanceof Node\Stmt\Namespace_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Node\Stmt\Use_) {
                        $this->replaceUseForAttributes($stmt);
                    }
                    if ($stmt instanceof Node\Stmt\Class_) {
                        $this->parseClass($stmt);
                    }
                }
            }
        }
    }

    protected function replaceUseForAttributes(Node\Stmt\Use_ $node): void
    {
        foreach ($node->uses as $use) {
            if ($use->name->parts == ['App', 'Annotation']) {
                $use->name->parts = ['App', 'Attribute'];
                $use->alias = 'IA';
            }
        }
    }

    protected function getAttributesFromString(string $string): array
    {
        $pattern = '|@IS\\\([a-zA-Z]+)(.*)|';

        if (preg_match_all($pattern, $string, $matches)) {
            $argument = null;
            if (isset($matches[2])) {
                $argument = trim($matches[2][0], '()"');
            }
            return [
                'name' => $matches[1][0],
                'argument' => $argument
            ];
        }

        return [];
    }

    protected function parseProperty(Node\Stmt\Property $node): void
    {
        $docBlock = $node->getDocComment();
        if (!$docBlock) {
            return;
        }
        $string = $docBlock->getReformattedText();
        $lines = explode("\n", $string);
        $linesWithDTOAnnotations = array_filter($lines, fn(string $line) => str_contains($line, '@IS'));
        $linesWithoutDTOAnnotations = array_filter($lines, fn(string $line) => !str_contains($line, '@IS'));
        $linesWithoutEmptyLines = array_filter($linesWithoutDTOAnnotations, fn(string $line) => strcmp($line, ' *') !== 0);
        $node->setDocComment(new Doc(implode("\n", $linesWithoutEmptyLines)));

        foreach ($linesWithDTOAnnotations as $str) {
            $annotation = $this->getAttributesFromString($str);
            if ($annotation) {
                $arguments = [];
                if ($annotation['argument']) {
                    $value = new Node\Scalar\String_($annotation['argument']);
                    $arguments[] = new Node\Arg(
                        $value,
                    );
                }
                $attribute = new Node\Attribute(new Node\Name('IA\\' . $annotation['name']), $arguments);

                $node->attrGroups[] = new Node\AttributeGroup([$attribute]);
            }
        }
    }

    protected function parseClass(Node\Stmt\Class_ $node): void
    {
        $docBlock = $node->getDocComment();
        if (!$docBlock) {
            return;
        }
        $string = $docBlock->getReformattedText();
        $lines = explode("\n", $string);
        $linesWithDTOAnnotations = array_filter($lines, fn(string $line) => str_contains($line, '@IS'));
        $linesWithoutDTOAnnotations = array_filter($lines, fn(string $line) => !str_contains($line, '@IS'));
        $linesWithoutEmptyLines = array_filter($linesWithoutDTOAnnotations, fn(string $line) => strcmp($line, ' *') !== 0);
        $node->setDocComment(new \PhpParser\Comment\Doc(implode("\n", $linesWithoutEmptyLines)));

        foreach ($linesWithDTOAnnotations as $str) {
            $annotation = $this->getAttributesFromString($str);
            if ($annotation) {
                $arguments = [];
                if ($annotation['argument']) {
                    $value = new Node\Scalar\String_($annotation['argument']);
                    $arguments[] = new Node\Arg(
                        $value,
                    );
                }
                $attribute = new Node\Attribute(new Node\Name('IA\\' . $annotation['name']), $arguments);

                $node->attrGroups[] = new Node\AttributeGroup([$attribute]);
            }
        }
        foreach ($node->getProperties() as $property) {
            $this->parseProperty($property);
        }
    }
}

class Mod extends AbstractCodemod
{
    public function init() {
        $visitor = new DTOVisitor();
        $this->addTraversalTransform($visitor);
    }
}

return Mod::class;