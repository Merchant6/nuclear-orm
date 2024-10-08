<?php

use DI\Container;
use DI\ContainerBuilder;

//Helper functions

if(!function_exists('container')){
    function container(): Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(__DIR__ . '/../config/definitions.php');
        return $builder->build();
    }
}

/**
 * @param string[] $from
 * @param string[] $against
 * @return string[]
 */
function diff(array $from, array $against): array
{
    return array_diff($from, $against);
}