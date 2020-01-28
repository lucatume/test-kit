<?php
putenv('TEST_ROOT_DIR=' . __DIR__);

// In monorepo context.
$utilsAutoload = dirname(__DIR__) . '/../utils/vendor/autoload.php';
if (file_exists($utilsAutoload)) {
    require_once $utilsAutoload;
}
