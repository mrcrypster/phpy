<?php

# script to create new project

if ( !$path = $argv[1] ) {
  return print 'Please input full path to a new project' . "\n";
}

$path = realpath($path);
@mkdir($path);

echo 'Creating new project in "' . $path . '"...' . "\n";

mkdir($path . '/web');
file_put_contents($path . '/web/index.php',
  '<?php' . "\n\n" . 
  "require_once '/var/www/phpy/build/phpy.php';" . "\n" .
  "phpy(['config' => ['root' => __DIR__ ]]);" . "\n"
);

mkdir($path . '/lib');
mkdir($path . '/com');
mkdir($path . '/com/layout');
file_put_contents($path . '/com/layout/index.php',
  '<?php return [' . "\n" . 
  "  ['html' => 'Hi there!']" . "\n" .
  "];" . "\n"
);

echo 'Done' . "\n";