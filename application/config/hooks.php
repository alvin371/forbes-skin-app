<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/userguide3/general/hooks.html
|
*/

$hook['pre_controller'][] = array(
    'class'    => 'RequestPerformanceHook',
    'function' => 'start',
    'filename' => 'RequestPerformanceHook.php',
    'filepath' => 'hooks',
);

$hook['post_controller'][] = array(
    'class'    => 'RequestPerformanceHook',
    'function' => 'finish',
    'filename' => 'RequestPerformanceHook.php',
    'filepath' => 'hooks',
);
