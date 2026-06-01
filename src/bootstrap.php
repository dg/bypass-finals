<?php declare(strict_types=1);

// Designed to be loaded BEFORE vendor/autoload.php so that classes
// registered in autoload files are processed by BypassFinals.
//
// Usage in tests/bootstrap.php:
//   require __DIR__ . '/../vendor/dg/bypass-finals/src/bootstrap.php';
//   require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/NativeWrapper.php';
require_once __DIR__ . '/MutatingWrapper.php';
require_once __DIR__ . '/BypassFinals.php';

DG\BypassFinals::enable();
