<?php

namespace Qpaso\Parser\Node;

use \JsonSerializable;
use \ReflectionClass;

class CommaGroup extends GroupNode
{
    public $separator = ", ";
}