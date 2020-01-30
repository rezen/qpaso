<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class ListGroup extends GroupNode
{
   
    public $fmt = "[%s]";
    public $separator = " ";
}
