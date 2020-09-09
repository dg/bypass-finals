<?php
declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable();

$customHandler = static function ($type, $msg, $file, $line) {
	throw new \ErrorException($msg, 0, $type, $file, $line);
};
set_error_handler($customHandler);

try {
	include 'file/no/found';
} catch (\ErrorException $e) {
	// Custom logic if file is missing
} finally {
	restore_error_handler();
}

require __DIR__ . '/fixtures/final.class.php';
$rc = new ReflectionClass('FinalClass');
Assert::false($rc->isFinal());
