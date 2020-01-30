<?php

namespace Qpaso\Executor\Resolvers;

class NotResolver implements ExecutionResolver
{
    function execute($data): bool
    {
        if (is_array($data)) {
            return empty($data);
        }
        return !$data;
    }
}