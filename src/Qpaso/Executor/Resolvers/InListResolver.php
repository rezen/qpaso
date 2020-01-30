<?php

namespace Qpaso\Executor\Resolvers;

class InListResolver implements ExecutionResolver
{
    public $argRules = [
        '0' => 'collection'
    ];

    function execute($data): bool
    {
        return in_array($data, $this->args[0]->items);
    } 
}
