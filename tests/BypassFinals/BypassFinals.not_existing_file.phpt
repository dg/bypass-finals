<?php

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();

Assert::noError(function () {
	file_put_contents(__DIR__ . '/fixtures/not_existing_class.php', 'test');
});

@unlink(__DIR__ . '/fixtures/not_existing_class.php');
