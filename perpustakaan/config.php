<?php

define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '/perpustakaan/');

function debug($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}
?>