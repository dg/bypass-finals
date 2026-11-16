<?php declare(strict_types=1);

// test wasFinal() with cache directory

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


BypassFinals::enable();
BypassFinals::setCacheDirectory(getTempDir());

require __DIR__ . '/fixtures/was-final.php';

Assert::false((new ReflectionClass(WasFinal\FinalClass::class))->isFinal());
Assert::true(BypassFinals::wasFinal(WasFinal\FinalClass::class));
Assert::false(BypassFinals::wasFinal(WasFinal\RegularClass::class));

// the stream wrapper is still active after reading the original source
require __DIR__ . '/fixtures/final.class.php';
Assert::false((new ReflectionClass('FinalClass'))->isFinal());
