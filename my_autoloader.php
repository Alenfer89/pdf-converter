<?php
spl_autoload_register(function ($class_name) {
    var_dump($class_name);
    include $class_name . '.php';
});
