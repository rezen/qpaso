<?php

namespace Qpaso\Executor\Resolvers;

class FirstResolver implements ExecutionResolver
{
    public $expects = ['indexable'];

    function execute($data)
    {
        return $data[0];
    } 
}