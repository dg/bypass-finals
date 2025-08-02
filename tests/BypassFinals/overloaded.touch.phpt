<?php declare(strict_types=1);

// test overloaded touch()

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

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
