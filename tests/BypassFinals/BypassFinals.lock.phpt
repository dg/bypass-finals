<?php

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable();

Assert::noError(function () {
	file_put_contents(__DIR__ . '/fixtures/tmp', 'foo', LOCK_EX);
});
