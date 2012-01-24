#!/usr/bin/env php
<?php

phar::mapPhar('spine.phar');

phar::interceptFileFuncs();
require 'phar://spine.phar/spine.php';

__HALT_COMPILER();