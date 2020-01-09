<?php
putenv('TEST_ROOT_DIR=' . __DIR__ );
putenv('VENDOR_ROOT_DIR=' . dirname(__DIR__) . '/vendor');

// In monorepo context.
$utilsAutoload = dirname(__DIR__) . '/../utils/vendor/autoload.php';
if (file_exists($utilsAutoload)) {
    require_once $utilsAutoload;
}

$streamWrappersAutoload = dirname(__DIR__) . '/../stream-wrappers/vendor/autoload.php';
if (file_exists($streamWrappersAutoload)) {
    require_once $streamWrappersAutoload;
}
