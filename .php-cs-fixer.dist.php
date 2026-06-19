<?php

use CodeIgniter\CodingStandard\CodeIgniter4;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->files()
    ->in(array(
        __DIR__ . '/application',
        __DIR__ . '/migrations',
        __DIR__ . '/tests',
        __DIR__ . '/tools',
    ))
    ->append(array(
        __DIR__ . '/index.php',
    ))
    ->name('*.php')
    ->ignoreVCS(true);

return Factory::create(new CodeIgniter4(), array(), array(
    'finder' => $finder,
))->forProjects();
