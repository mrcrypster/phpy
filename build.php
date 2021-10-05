<?php

# Helper script to build production-ready framework files

echo 'Building files...' . "\n";

file_put_contents(__DIR__ . '/build/phpy.php',
                   file_get_contents(__DIR__ . '/src/core.php') . "\n" .
                   file_get_contents(__DIR__ . '/src/utilities.php')
                 );
                 
file_put_contents(__DIR__ . '/build/phpy.js',
                   file_get_contents(__DIR__ . '/src/phpy.js')
                 );
                 
file_put_contents(__DIR__ . '/build/phpy.css',
                   file_get_contents(__DIR__ . '/src/phpy.css')
                 );
                 
echo 'Ok, done!' . "\n";