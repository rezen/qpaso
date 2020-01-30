<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class TextNode extends Node
{
    public function __toString()
    {
        return $this->value;
    }
}

