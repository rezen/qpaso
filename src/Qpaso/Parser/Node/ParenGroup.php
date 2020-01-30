<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class ParenGroup extends GroupNode
{
    public $fmt = "(%s)";
}
