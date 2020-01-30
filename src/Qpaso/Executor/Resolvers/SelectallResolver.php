<?php

namespace Qpaso\Executor\Resolvers;

class SelectallResolver implements ExecutionResolver
{
    function execute($data): array
    {
        return [
            ['href'=> 'http://test.com'], 
            ['href'=> '/about'], 
            ['href'=> '/help']
        ];
    }
}