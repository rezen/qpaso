<?php

namespace Qpaso\Executor\Resolvers;

class MapResolver implements ExecutionResolver
{
    public $expects = ['collection'];

    function execute($data): array
    {
       
       
        if (\Qpaso\isAssociative($this->args)) {


            $keys = array_keys($this->args);
            return array_map(
                function ($row) use ($keys) {
                    return array_reduce(
                        $keys, function ($aggr, $key) use ($row) {
                            $aggr[$key] = \Qpaso\resolveInstructions($row, $this->args[$key]);
                            return $aggr;
                        }, []
                    );
                }, $data
            );
        }
        return array_map(
            function ($row) {
                return \Qpaso\resolveInstructions($row, $this->args);
            }, $data
        ); 
    }
}