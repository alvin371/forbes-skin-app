<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Memcached settings
| -------------------------------------------------------------------------
| Your Memcached servers can be specified below.
|
|	See: https://codeigniter.com/userguide3/libraries/caching.html#memcached
|
*/
// Environment-based Memcached configuration
$memcached_host = 'localhost';
$memcached_port = '11211';

// Check if we're on the production/staging server
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'bhskin.co.id') !== false) {
    // Production/staging server - use localhost
    $memcached_host = 'localhost';
} elseif (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost') {
    // Local development
    $memcached_host = '127.0.0.1';
}

$config = array(
	'default' => array(
		'hostname' => $memcached_host,
		'port'     => $memcached_port,
		'weight'   => '1',
	),
);
