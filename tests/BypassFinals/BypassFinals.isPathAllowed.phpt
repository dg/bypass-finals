<?php declare(strict_types=1);

// test isPathAllowed()

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

Assert::with(BypassFinals::class, function () {
	Assert::true(BypassFinals::isPathAllowed(__DIR__ . '/fixtures/final.class.php'));


	DG\BypassFinals::allowPaths([
		__DIR__ . '/fixtures/final.class.php',
	]);

	Assert::true(BypassFinals::isPathAllowed(__DIR__ . '/fixtures/final.class.php'));
	Assert::false(BypassFinals::isPathAllowed(__DIR__ . '/fixtures/other.class.php'));


	DG\BypassFinals::setWhitelist([]); // reset whitelist

	DG\BypassFinals::allowPaths([
		__DIR__ . '/fixtures/*',
	]);

	Assert::true(BypassFinals::isPathAllowed(__DIR__ . '/fixtures/class.php'));
	Assert::false(BypassFinals::isPathAllowed(__DIR__ . '/other/class.php'));


	DG\BypassFinals::denyPaths([
		__DIR__ . '/fixtures/class.php',
	]);

	Assert::false(BypassFinals::isPathAllowed(__DIR__ . '/fixtures/class.php'));
	Assert::false(BypassFinals::isPathAllowed(__DIR__ . '/other/class.php'));
});
