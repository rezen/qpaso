<?php

namespace Qpaso\Executor;

use Qpaso\Parser\Node\Node;
use Qpaso\Parser\Node\GroupNode;
use Qpaso\Parser\Node\ParenGroup;
use Qpaso\Parser\Node\ListGroup;
use Qpaso\Parser\Node\CommaGroup;
use Qpaso\Parser\Node\NamedArg;
use Qpaso\Executor\Primitives\ListPrimitive;

class InstructionsBuilder
{
    public $resolver;
    public $looseStrings = true;


    function __construct()
    {
        $this->resolver = new NodeResolver();
    }
  
    function nodeChildren($node)
    {
        if ($node->isEmpty()) {
            return [];
        }

        if (count($node->children) === 1) {
            // To flatten things out ...
            return $this->build($node->children[0], $node); 
        }

        $items = array_map(
            function ($n) use ($node) {
                $tree =  $this->build($n, $node);
                return $tree;
            }, $node->children
        );

        return $this->remapArrayForNamedArgs($items);
    }

    /**
     * If is an array of associative arrays merge array
     */
    function remapArrayForNamedArgs(array $items): array
    {
        $allAssociative = count(array_filter($items, '\\Qpaso\\isAssociative'));
        if (!$allAssociative) {
            return $items;
        }

        $itemsWithKeys = array_map(null, array_keys($items), $items);
        return array_reduce(
            $itemsWithKeys, function ($aggr, $pair) {
                [$index, $item] = $pair;

                // If item is not an associative array get its positional index
                if (!is_array($item)) {
                    $aggr[$index] = $item;
                    return $aggr;
                }
                return array_reduce(
                    array_keys($item), function ($a, $key) use ($item) {
                        $a[$key] = $item[$key];
                        return $a;
                    }, $aggr
                );
            }, []
        );
    }

    function build($node)
    {
        $value = $this->resolver->resolveNode($node);
        $kids = $this->nodeChildren($node);
    
        if ($this->resolver->isResolvableFunction($node)) {
            $value->args = is_array($kids) ? $kids : [$kids];
            return $value;
        }

        if (\Qpaso\isAssociative($kids)) {
            return $kids;
        }

        switch (get_class($node)) {
        case ListGroup::class:
            return new ListPrimitive($kids);
        case CommaGroup::class:
            return $kids;
        case NamedArg::class:
            $kids = !is_array($kids) ? $kids : new Pipeline($kids);
            return [$value => $kids]; 
        case GroupNode::class:
        case ParenGroup::class:
            return !is_array($kids) ? $kids : new Pipeline($kids);
        default:
            break;
        }

        return empty($kids) ? $value : [$value, $kids];
    }
}

