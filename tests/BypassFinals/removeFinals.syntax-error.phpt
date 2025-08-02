<?php declare(strict_types=1);

// test that removeFinals() catches token_get_all() exception

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

DG\BypassFinals::enable();

file_put_contents(__DIR__ . '/fixtures/invalid.php', '<?php final class;');

Assert::exception(function () {
	require __DIR__ . '/fixtures/invalid.php';
}, ParseError::class);

@unlink(__DIR__ . '/fixtures/invalid.php');
