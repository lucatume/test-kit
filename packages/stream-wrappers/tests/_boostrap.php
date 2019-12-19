<?php
putenv('TEST_ROOT_DIR=' . __DIR__);

$utilsSrc = dirname(__DIR__) . '/../utils/src';

if (file_exists($utilsSrc)) {
    // In monorepo context.
    foreach (glob($utilsSrc . '/*.php') as $file) {
        require_once $file;
    }

    $autoloader =  new Composer\Autoload\ClassLoader();
    $autoloader->addPsr4('tad\\Utils\\', $utilsSrc);
    $autoloader->register();
}
