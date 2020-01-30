<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class Node implements JsonSerializable
{
    public $value;
    public $children = [];

    function __construct($value, array $children=[])
    {
        $this->value = $value;
        $this->children = $children;
    }

    public function __toString()
    {
        if (count($this->children) === 0) {
            return $this->value;
        }
        $children = array_map(
            function ($n) {
                return "$n";
            }, $this->children
        );
        $children = implode(", ", $children);
        return $this->value . "($children)"; 
    }

    function convert($className)
    {
        return new $className($this->value, $this->children);
    }

    function hasLiveVariable()
    {
        return !is_null($this->var ?? null);
    }

    function addChild($node)
    {
        if ($this->hasLiveVariable()) {
            $this->var->addChild($node);
            return;
        }
        $this->children[] = $node;
    }

    public function jsonSerialize()
    {
        return [
            'value'   => $this->value,
            'class'  => (new ReflectionClass($this))->getShortName(),
            'children' => $this->children,
        ];
    }

    function isEmpty()
    {
        if (count($this->children) === 0) {
            return true;
        }

       
        $items = array_filter(
            $this->children, function ($n) {
                return ($n instanceof GroupNode || $n instanceof ParenGroup) && $n->isEmpty();
            }
        );

        return count($items) !== 0;
    }
}













