<?php

$targets = [
    __DIR__ . '/../application',
    __DIR__ . '/../migrations',
    __DIR__ . '/../tests',
    __DIR__ . '/../tools',
    __DIR__ . '/../index.php',
];

$files = [];

foreach ($targets as $target) {
    if (is_file($target)) {
        $files[] = $target;

        continue;
    }

    if (! is_dir($target)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $files[] = $file->getPathname();
    }
}

sort($files);

$failures = [];

foreach ($files as $file) {
    $command = escapeshellcmd(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1';
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        $failures[$file] = implode(PHP_EOL, $output);
    }
}

if ($failures !== []) {
    foreach ($failures as $file => $message) {
        fwrite(STDERR, 'Syntax lint failed for ' . $file . PHP_EOL);
        fwrite(STDERR, $message . PHP_EOL);
    }

    exit(1);
}

fwrite(STDOUT, 'Syntax lint passed for ' . count($files) . ' file(s).' . PHP_EOL);
