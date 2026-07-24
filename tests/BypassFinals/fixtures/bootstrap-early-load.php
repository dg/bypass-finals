<?php declare(strict_types=1);

// Child process for BypassFinals.bootstrap.phpt.
// Activates BypassFinals via src/bootstrap.php BEFORE loading a final class the way
// Composer loads autoload.files entries: the wrapper is active, so `final` is stripped.

require __DIR__ . '/../../../src/bootstrap.php';
require __DIR__ . '/final.class.php';

echo (new ReflectionClass(FinalClass::class))->isFinal() ? 'has-final' : 'not-final';
