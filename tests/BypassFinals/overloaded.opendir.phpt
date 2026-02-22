<?php declare(strict_types=1);

// test overloaded opendir()

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();

$dir = opendir(__DIR__ . '/fixtures');
while (($file = readdir($dir)) !== false) {
	$files[] = $file;
}
closedir($dir);

sort($files);
Assert::same([
	'.',
	'..',
	'final.class.php',
	'final.excluded.class.php',
	'final.readonly.class.php',
	'magic.constants.php',
], $files);
