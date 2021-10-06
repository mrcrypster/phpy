<?php

$t = microtime(1);

require_once __DIR__ . '/../../../src/core.php';
require_once __DIR__ . '/../../../src/utilities.php';
phpy([
  'config' => [
    'root' => __DIR__,
    'acl' => [
      'public' => '/^\/login.*/',
      'private' => '/^.*$/',
    ]
  ]
]);

echo microtime(1) - $t;