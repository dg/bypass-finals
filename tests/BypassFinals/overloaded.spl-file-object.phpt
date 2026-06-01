<?php declare(strict_types=1);

// Regression test for issue #50:
// SplFileObject::seek(PHP_INT_MAX)->key() must return the same line count
// with and without BypassFinals active. The stream wrapper was previously
// reporting EOF too early (after the last non-empty read), causing the count
// to be off by one for files ending with a newline.

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$tmpFile = tempnam(sys_get_temp_dir(), 'bypass_spl_');
file_put_contents($tmpFile, implode("\n", range(1, 10)) . "\n");

$countLines = function (string $path): int {
	$spl = new SplFileObject($path);
	$spl->seek(PHP_INT_MAX);
	return $spl->key();
};

$before = $countLines($tmpFile);

DG\BypassFinals::enable();

$after = $countLines($tmpFile);

@unlink($tmpFile);

Assert::same($before, $after);
