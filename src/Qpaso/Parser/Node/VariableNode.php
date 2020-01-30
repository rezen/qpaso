<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class VariableNode extends Node
{
    public function __toString()
    {
        if ($this->children[0] instanceof GroupNode) {
            return "{$this->value}=({$this->children[0]})";
        }
        return "{$this->value}={$this->children[0]}";
    }
}
