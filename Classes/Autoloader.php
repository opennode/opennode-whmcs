<?php
class AutoLoader {

    public static function loadClass($className) {
        $filename = dirname(__FILE__) ."/". str_replace('\\', '/', $className) . ".php";
        if (file_exists($filename)) {
            include_once ($filename);
            if (class_exists($className)) {
                return TRUE;
            }
        }
        return FALSE;
    }

}

spl_autoload_register(array(
    'AutoLoader',
    'loadClass'
));
?>