<?php

namespace Qpaso\Executor\Resolvers;

class ContainsResolver implements ExecutionResolver
{
    public $expects = ['stringable'];
    
    function execute($data): bool
    {
        return (strpos($data, $this->args[0]) !== false);
    } 
}
