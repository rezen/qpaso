<?php

namespace Qpaso\Executor\Resolvers;

class SplitResolver implements ExecutionResolver
{
    public $expects = ['stringable'];
    public $argRules = [
        '0' => 'stringable'
    ];
    function execute($data): array
    {
        $delimiter = $this->args[0] ?? "";
        if ($delimiter === "") {
            return str_split("{$data}");
        }
        return explode($delimiter, "{$data}");
    }
}