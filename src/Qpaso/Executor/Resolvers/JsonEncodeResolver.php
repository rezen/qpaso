<?php

namespace Qpaso\Executor\Resolvers;

class JsonEncodeResolver implements ExecutionResolver
{
    function execute($data): string
    {
        if (($this->args['pretty'] ?? null) == 1) {
            return json_encode($data, JSON_PRETTY_PRINT);
        }
        
        // @todo flag for pretty-print
        return json_encode($data);
    }
}
     