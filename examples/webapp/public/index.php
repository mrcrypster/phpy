<?php

$t = microtime(1);

require_once __DIR__ . '/../../../phpy.php';
phpy(__DIR__);

echo microtime(1) - $t;