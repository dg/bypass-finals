<?php declare(strict_types=1);

// Child process for BypassFinals.bootstrap.phpt (issue #58).
//
// $argv[1] selects the order in which two things happen:
//   - activating BypassFinals via src/bootstrap.php
//   - loading a final class the way Composer loads autoload.files entries
//     (a plain require that runs during `require vendor/autoload.php`)
//
// 'before' = bootstrap first  -> wrapper active -> `final` is stripped
// 'after'  = class first      -> class already final by the time the wrapper starts

$bootstrap = function () { require __DIR__ . '/../../../src/bootstrap.php'; };
$loadClass = function () { require __DIR__ . '/final.class.php'; };

if ($argv[1] === 'before') {
	$bootstrap();
	$loadClass();
} else {
	$loadClass();
	$bootstrap();
}

echo (new ReflectionClass(FinalClass::class))->isFinal() ? 'has-final' : 'not-final';
