<?php

namespace Qpaso\Executor;

class Pipeline
{
    public $executors = [];

    function __construct($f)
    {
        $this->executors = $f;
    }
}
