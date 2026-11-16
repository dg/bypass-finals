<?php declare(strict_types=1);

// test wasFinal()

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


BypassFinals::enable();
BypassFinals::denyPaths([
	'*/fixtures/was-final.denied.php',
]);

require __DIR__ . '/fixtures/was-final.php';
require __DIR__ . '/fixtures/was-final.denied.php';
require __DIR__ . '/fixtures/was-final.namespaces.php';

// stripped final classes
Assert::false((new ReflectionClass(WasFinal\FinalClass::class))->isFinal());
Assert::true(BypassFinals::wasFinal(WasFinal\FinalClass::class));
Assert::true(BypassFinals::wasFinal(new ReflectionClass(WasFinal\FinalClass::class)));
Assert::false((new ReflectionClass(WasFinal\MultiLineClass::class))->isFinal());
Assert::true(BypassFinals::wasFinal(WasFinal\MultiLineClass::class));

// several classes on one line, same short name in different namespaces
Assert::true(BypassFinals::wasFinal(WasFinal\SameLineFinal::class));
Assert::false(BypassFinals::wasFinal(WasFinal\SameLineRegular::class));
Assert::true(BypassFinals::wasFinal(WasFinal\A\X::class));
Assert::false(BypassFinals::wasFinal(WasFinal\B\X::class));
Assert::true(BypassFinals::wasFinal(WasFinal\B\Y::class));

// classes that were never final, in a modified file
Assert::false(BypassFinals::wasFinal(WasFinal\RegularClass::class));
Assert::false(BypassFinals::wasFinal(WasFinal\AbstractClass::class));

// a final class left untouched
Assert::true((new ReflectionClass(WasFinal\DeniedClass::class))->isFinal());
Assert::true(BypassFinals::wasFinal(WasFinal\DeniedClass::class));

// internal and anonymous classes
Assert::false(BypassFinals::wasFinal(ArrayObject::class));
Assert::true(BypassFinals::wasFinal(Closure::class));
Assert::false(BypassFinals::wasFinal(new ReflectionClass(new class {})));

// unknown class
Assert::exception(
	fn() => BypassFinals::wasFinal('WasFinal\Unknown'),
	ReflectionException::class,
);
