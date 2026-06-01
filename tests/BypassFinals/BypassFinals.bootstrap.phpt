<?php declare(strict_types=1);

// Regression test for issue #58:
// src/bootstrap.php activates BypassFinals on its own (no Composer autoloader
// needed), so it can be required BEFORE vendor/autoload.php and strip `final`
// from classes that Composer loads during autoloading (e.g. via autoload.files).
//
// The child process loads a final class either AFTER activating BypassFinals
// (the bootstrap.php use case) or BEFORE it (the broken ordering). Asserting
// both proves the test is sensitive to the ordering, not a tautology.

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function runChild(string $order): array
{
	$script = __DIR__ . '/fixtures/bootstrap-early-load.php';
	$proc = proc_open(
		escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($order),
		[1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
		$pipes,
		null,
		null,
		['bypass_shell' => true] // Windows: avoid cmd.exe stripping the outer quotes
	);
	$output = stream_get_contents($pipes[1]);
	$error = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	$code = proc_close($proc);
	return [trim($output), trim($error), $code];
}


// bootstrap.php first: the wrapper is active before the class loads -> final stripped
[$output, $error, $code] = runChild('before');
Assert::same('', $error);
Assert::same(0, $code);
Assert::contains('not-final', $output);

// control: class loads first -> it is already final when the wrapper starts
[$output, $error, $code] = runChild('after');
Assert::same('', $error);
Assert::same(0, $code);
Assert::contains('has-final', $output);
