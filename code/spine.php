<?php

require_once 'src/autoload.php';
require_once 'vendor/Markdown/Markdown/Parser.php';
require_once 'vendor/markdownify/markdownify.php';

define('ROOT', __DIR__);

use Symfony\Component\Console as Console;

$application = new Console\Application('spine', '0.9');
$application->add(new Spine\Command\RenderCommand());
$application->add(new Spine\Command\ExtractCommand());
$application->run();