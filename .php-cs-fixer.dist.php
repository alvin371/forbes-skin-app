<?php

use CodeIgniter\CodingStandard\CodeIgniter4;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$paths = array_filter(array(
    __DIR__ . '/application',
    __DIR__ . '/migrations',
    __DIR__ . '/tests',
    __DIR__ . '/tools',
), 'is_dir');

$finder = Finder::create()
    ->files()
    ->in($paths)
    ->append(array(
        __DIR__ . '/index.php',
    ))
    ->name('*.php')
    ->ignoreVCS(true);

return Factory::create(new CodeIgniter4(), array(), array(
    'finder' => $finder,
))->forProjects();
