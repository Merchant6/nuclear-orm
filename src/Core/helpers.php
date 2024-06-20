<?php

use DI\ContainerBuilder;

//Helper functions

if(!function_exists('container')){
    function container()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(__DIR__ . '/../config/definitions.php');
        return $builder->build();
    }
}