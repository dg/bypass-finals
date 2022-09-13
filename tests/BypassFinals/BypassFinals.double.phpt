<?php

// test double called enable()

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();
DG\BypassFinals::enable();

Assert::noError(function () {
	require __DIR__ . '/fixtures/final.class.php';
});
