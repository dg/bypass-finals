<?php declare(strict_types=1);

// test overloaded mkdir()

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable();


@rmdir(__DIR__ . '/temp/sub');
@rmdir(__DIR__ . '/temp');

Assert::error(function () { // not recursive
	mkdir(__DIR__ . '/temp/sub');
}, E_WARNING);

Assert::noError(function () { // recursive
	mkdir(__DIR__ . '/temp/sub', 0o777, true);
});

@rmdir(__DIR__ . '/temp/sub');
@rmdir(__DIR__ . '/temp');
