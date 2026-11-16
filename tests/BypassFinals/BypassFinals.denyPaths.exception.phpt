<?php declare(strict_types=1);

// test '!' exception masks in denyPaths()

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable();
DG\BypassFinals::denyPaths([
	'*/fixtures/denied/*',
	'!*/fixtures/denied/excepted/*',
]);

require __DIR__ . '/fixtures/denied/denied.class.php';
require __DIR__ . '/fixtures/denied/excepted/excepted.class.php';
require __DIR__ . '/fixtures/final.class.php';

Assert::true((new ReflectionClass('DeniedClass'))->isFinal());
Assert::false((new ReflectionClass('ExceptedClass'))->isFinal());
Assert::false((new ReflectionClass('FinalClass'))->isFinal());
