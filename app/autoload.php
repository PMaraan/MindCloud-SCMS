<?php
// /app/autoload.php

/**
 * PSR-4 style autoloader for the App\ namespace.
 * This lets us load classes automatically based on namespaces.
 */

spl_autoload_register(function ($class) {
    // The namespace prefix
    $prefix = 'App\\';

    // base directory for the namespace prefix
    $baseDir = __DIR__ . '/';

    // Only autoload classes in our App\
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Remove namespace prefix
    $relativeClass = substr($class, $len);

    // Convert namespace to path
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Load the file if it exists
    if (file_exists($file)) {
        require $file;
    }
});
