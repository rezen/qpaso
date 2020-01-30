<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class FunctionNode extends Node
{
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
        return $this->value . "$children"; 
    }
}