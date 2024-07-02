<?php

// test denyPaths()

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();
DG\BypassFinals::denyPaths([
	'*/fixtures/final.class.php',
]);

require __DIR__ . '/fixtures/final.class.php';
require __DIR__ . '/fixtures/final.excluded.class.php';

$rc = new ReflectionClass('FinalClass');
Assert::true($rc->isFinal());

$rc = new ReflectionClass('FinalClassExcluded');
Assert::false($rc->isFinal());
