<?php

// test overloaded mkdir()

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();


@rmdir(__DIR__ . '/temp/sub');
@rmdir(__DIR__ . '/temp');

Assert::error(function () { // not recursive
	mkdir(__DIR__ . '/temp/sub');
}, E_WARNING);

Assert::noError(function () { // recursive
	mkdir(__DIR__ . '/temp/sub', 0777, true);
});

@rmdir(__DIR__ . '/temp/sub');
@rmdir(__DIR__ . '/temp');
