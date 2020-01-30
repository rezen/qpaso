<?php

namespace Qpaso;

class Cursor
{
    public $items;
    public $index;

    function next() 
    {
        $this->index += 1;
        return $this->items[$this->index];
    }

    function peek()
    {
        return $this->items[$this->index + 1] ?? null;
    }

    function prev()
    {
        return $this->items[$this->index - 1] ?? null;
    }
}

