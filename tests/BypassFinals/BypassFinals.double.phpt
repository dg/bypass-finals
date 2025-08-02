<?php declare(strict_types=1);

// test double called enable()

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable();
DG\BypassFinals::enable();

Assert::noError(function () {
	require __DIR__ . '/fixtures/final.class.php';
});
