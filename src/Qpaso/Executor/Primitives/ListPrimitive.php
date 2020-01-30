<?php

namespace Qpaso\Executor\Primitives;

class ListPrimitive
{
    public $items = [];

    function __construct($items)
    {
        $this->items = is_array($items) ? $items : [$items];
    }
}
