<?php

namespace Qpaso\Executor\Resolvers;

class OrResolver implements ExecutionResolver
{
    public $isVariadic = true;
    public $argRules = [
        '0' => 'collection'
    ];

    function execute($data): bool
    {
        foreach ($this->args[0]->executors as $fn) {
            $val = \Qpaso\resolveInstructions($data, $fn);
            if ($val) {
                return true;
            }
           
        }
        return false;
    } 
}

