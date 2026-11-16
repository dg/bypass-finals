<?php declare(strict_types=1);

// test isPathAllowed()

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

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


	// '!' masks in denyPaths() are exceptions to the deny rules
	DG\BypassFinals::setWhitelist(['*']); // reset whitelist
	DG\BypassFinals::denyPaths([
		'*/src/*',
		'!*/src/Mapper/*',
	]);

	Assert::true(BypassFinals::isPathAllowed('/app/src/Mapper/Foo.php'));
	Assert::true(BypassFinals::isPathAllowed('C:\\app\\src\\Mapper\\Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/src/Model/Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/src/Mapper.php'));
	Assert::true(BypassFinals::isPathAllowed('/app/lib/Foo.php'));


	// the last matching mask wins, so a path can be denied again below an exception
	DG\BypassFinals::denyPaths([
		'*/src/Mapper/Generated/*',
	]);

	Assert::false(BypassFinals::isPathAllowed('/app/src/Mapper/Generated/Foo.php'));
	Assert::true(BypassFinals::isPathAllowed('/app/src/Mapper/Foo.php'));


	// an exception does not allow a path outside of allowPaths()
	DG\BypassFinals::setWhitelist([
		'*/lib/*',
	]);

	Assert::true(BypassFinals::isPathAllowed('/app/lib/Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/src/Mapper/Foo.php'));
});
