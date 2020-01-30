<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class GroupNode extends Node
{
    public $separator = " | ";
    public $fmt = "%s";

    public function jsonSerialize()
    {
        return $this->children;
    }
    
    public function __toString()
    {   
        if (count($this->children) === 0) {
            return "";
        }
        $children = array_map(
            function ($n) {
                return "$n";
            }, $this->children
        );
        $children = implode($this->separator, $children);

        // @todo insecure 
        return sprintf($this->fmt, $children);
    }
}
