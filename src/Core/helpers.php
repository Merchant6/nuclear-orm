<?php

if(!function_exists('env')){
    function env(string $variable){
        return $_ENV[$variable];
    }
}