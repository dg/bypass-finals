<?php declare(strict_types=1);

// test overloaded file_put_contents() with lock

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

DG\BypassFinals::enable();

Assert::noError(function () {
	file_put_contents(__DIR__ . '/fixtures/tmp', 'foo', LOCK_EX);
});

unlink(__DIR__ . '/fixtures/tmp');
