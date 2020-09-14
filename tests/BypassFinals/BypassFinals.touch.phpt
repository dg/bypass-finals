<?php
declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable();

Assert::noError(function () {
	touch('known');
});

Assert::noError(function () {
	touch('known', time());
});

Assert::error(function () {
	touch('known', 'foo');
}, TypeError::class);

unlink('known');
