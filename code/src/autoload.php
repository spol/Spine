<?php
// src/autoload.php
require_once __DIR__.'/../vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony' => __DIR__.'/../vendor',
//    'Markdown' => __DIR__.'/../vendor',
    'Spine'     => __DIR__
));
$loader->register();