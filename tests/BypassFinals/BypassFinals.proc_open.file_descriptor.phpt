<?php declare(strict_types=1);

// Regression test for issue #43:
// proc_open() with a ['file', path, mode] descriptor spec must redirect subprocess
// output to the file. Without the fix, stream_close() closes the underlying FD
// before the child inherits it, causing dup2 to fail and output to appear on the
// wrong stream.

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();

$tmpFile = tempnam(sys_get_temp_dir(), 'bypass_');

$proc = proc_open(
	'echo bypass_finals_test_output',
	[
		0 => ['pipe', 'r'],
		1 => ['file', $tmpFile, 'w'],
		2 => STDERR,
	],
	$pipes,
);

Assert::notSame(false, $proc);
proc_close($proc);

$content = file_get_contents($tmpFile);
@unlink($tmpFile);

Assert::contains('bypass_finals_test_output', $content);
