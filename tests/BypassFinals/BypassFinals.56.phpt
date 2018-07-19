<?php

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();

require __DIR__ . '/fixtures/final.class.56.php';

$rc = new ReflectionClass('FinalClass56');
Assert::false($rc->isFinal());
Assert::false($rc->getMethod('finalMethod')->isFinal());
