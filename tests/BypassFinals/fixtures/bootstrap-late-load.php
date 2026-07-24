<?php declare(strict_types=1);

// Child process for BypassFinals.bootstrap.phpt.
// Control case: the final class loads BEFORE BypassFinals is activated, so it is
// already compiled as final by the time the wrapper starts.

require __DIR__ . '/final.class.php';
require __DIR__ . '/../../../src/bootstrap.php';

echo (new ReflectionClass(FinalClass::class))->isFinal() ? 'has-final' : 'not-final';
