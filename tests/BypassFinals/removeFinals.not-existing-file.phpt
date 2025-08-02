<?php declare(strict_types=1);

// test that removing finals is done only in reading mode

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable();

Assert::noError(function () {
	file_put_contents(__DIR__ . '/fixtures/not_existing_class.php', 'test');
});

@unlink(__DIR__ . '/fixtures/not_existing_class.php');
