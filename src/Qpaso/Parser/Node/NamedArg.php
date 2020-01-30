<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class NamedArg extends Node
{
    function addChild($node)
    {
        if (count($this->children) !== 0) {
            throw new \Exception("A named arg cannot have multiple children");
        }
        parent::addChild($node);
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

        $children = implode(" ", $children);
        return $this->value . "=$children"; 
    }
}
