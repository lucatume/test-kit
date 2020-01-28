<?php
$r = global_user_function(23, 89);

if ( $r === 112) {
    throw new \RuntimeException('global_user_function was called w/ its original code');
}

echo  $r;
