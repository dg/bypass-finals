<?php declare(strict_types=1);

// test overloaded opendir()

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function listDir(string $path): array
{
	$files = [];
	$dir = opendir($path);
	while (($file = readdir($dir)) !== false) {
		$files[] = $file;
	}
	closedir($dir);
	sort($files);
	return $files;
}


// snapshot taken via the native handler, before BypassFinals is enabled
$expected = listDir(__DIR__ . '/fixtures');
Assert::true(count($expected) > 2); // sanity: more than just '.' and '..'

DG\BypassFinals::enable();

// the overloaded handler must return exactly the same listing
Assert::same($expected, listDir(__DIR__ . '/fixtures'));
