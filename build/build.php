<?php

$phar = new Phar('spine.phar', 0, 'spine.phar');

$phar->buildFromDirectory(__DIR__.'/../code');
$phar->setStub(file_get_contents(__DIR__.'/../code/stub.php'));