<?php declare(strict_types=1);

// test that wasFinal() keeps the currently registered stream wrapper

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


BypassFinals::enable();
require __DIR__ . '/fixtures/was-final.php';

// BypassFinals switched off by restoring the native wrapper
stream_wrapper_restore('file');

Assert::true(BypassFinals::wasFinal(WasFinal\FinalClass::class));

// wasFinal() did not switch BypassFinals on again
require __DIR__ . '/fixtures/final.class.php';
Assert::true((new ReflectionClass('FinalClass'))->isFinal());
