<?php

// test isPathInWhiteList()

declare(strict_types=1);

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

Assert::with(BypassFinals::class, function () {
	Assert::true(BypassFinals::isPathInWhiteList(__DIR__ . '/fixtures/final.class.php'));


	DG\BypassFinals::setWhitelist([
		__DIR__ . '/fixtures/final.class.php',
	]);

	Assert::true(BypassFinals::isPathInWhiteList(__DIR__ . '/fixtures/final.class.php'));
	Assert::false(BypassFinals::isPathInWhiteList(__DIR__ . '/fixtures/other.class.php'));


	DG\BypassFinals::setWhitelist([
		__DIR__ . '/fixtures/*',
	]);

	Assert::true(BypassFinals::isPathInWhiteList(__DIR__ . '/fixtures/class.php'));
	Assert::false(BypassFinals::isPathInWhiteList(__DIR__ . '/other/class.php'));
});
