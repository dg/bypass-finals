<?php declare(strict_types=1);

// test '!' exception masks in isPathAllowed(); each section starts from a clean state

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

Tester\Environment::setupFunctions();


function resetRules(): void
{
	(fn() => self::$accessRules = [])->bindTo(null, BypassFinals::class)();
}


test('denyPaths() exception', function () {
	resetRules();
	BypassFinals::denyPaths(['*/src/*', '!*/src/Mapper/*']);

	Assert::true(BypassFinals::isPathAllowed('/app/src/Mapper/Foo.php'));
	Assert::true(BypassFinals::isPathAllowed('C:\app\src\Mapper\Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/src/Model/Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/src/Mapper.php'));
	Assert::true(BypassFinals::isPathAllowed('/app/lib/Foo.php'));
});


test('denyPaths(): the last matching mask wins', function () {
	resetRules();
	BypassFinals::denyPaths(['*/src/*', '!*/src/Mapper/*']);
	BypassFinals::denyPaths(['*/src/Mapper/Generated/*']);

	Assert::false(BypassFinals::isPathAllowed('/app/src/Mapper/Generated/Foo.php'));
	Assert::true(BypassFinals::isPathAllowed('/app/src/Mapper/Foo.php'));
});


test('denyPaths() exception does not allow a path outside of allowPaths()', function () {
	resetRules();
	BypassFinals::allowPaths(['*/lib/*']);
	BypassFinals::denyPaths(['*/src/*', '!*/src/Mapper/*']);

	Assert::true(BypassFinals::isPathAllowed('/app/lib/Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/src/Mapper/Foo.php'));
});


test('denyPaths() with exceptions only denies nothing', function () {
	resetRules();
	BypassFinals::denyPaths(['!*/src/*']);

	Assert::true(BypassFinals::isPathAllowed('/app/src/Foo.php'));
	Assert::true(BypassFinals::isPathAllowed('/app/lib/Foo.php'));
});


test('allowPaths() exception, the last matching mask wins', function () {
	resetRules();
	BypassFinals::allowPaths(['*/lib/*', '!*/lib/Generated/*', '*/lib/Generated/Mockable/*']);

	Assert::true(BypassFinals::isPathAllowed('/app/lib/Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/lib/Generated/Foo.php'));
	Assert::true(BypassFinals::isPathAllowed('/app/lib/Generated/Mockable/Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/other/Foo.php'));
});


test('allowPaths() with exceptions only allows everything else', function () {
	resetRules();
	BypassFinals::allowPaths(['!*/Generated/*']);

	Assert::true(BypassFinals::isPathAllowed('/app/src/Foo.php'));
	Assert::false(BypassFinals::isPathAllowed('/app/src/Generated/Foo.php'));
});


test('an empty allow list still allows nothing', function () {
	resetRules();
	BypassFinals::setWhitelist([]);

	Assert::false(BypassFinals::isPathAllowed('/app/src/Foo.php'));
});
