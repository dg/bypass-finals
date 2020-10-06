<?php
// test setWhitelist()

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();
DG\BypassFinals::setWhitelist([
	'*/fixtures/final.class.php',
]);

require __DIR__ . '/fixtures/final.class.php';
require __DIR__ . '/fixtures/final.excluded.class.php';

$rc = new ReflectionClass('FinalClass');
Assert::false($rc->isFinal());

$rc = new ReflectionClass('FinalClassExcluded');
Assert::true($rc->isFinal());
