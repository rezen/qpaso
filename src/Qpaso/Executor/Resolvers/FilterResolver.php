<?php

namespace Qpaso\Executor\Resolvers;

class FilterResolver implements ExecutionResolver
{
   
    public $argRules = [

    ];
    public $expects = ['collection'];

    function execute($data): array
    {
        return array_filter(
            $data, function ($row) {
                $val =  \Qpaso\resolveInstructions($row, $this->args[0]);
                return $val;
            }
        );
    } 
}
