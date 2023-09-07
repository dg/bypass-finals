<?php

/** @phpVersion 8.2 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable(bypassReadOnly: false);

require __DIR__ . '/fixtures/final.readonly.class.php';

$rc = new ReflectionClass('FinalReadonlyClass');
Assert::true($rc->isReadOnly());
Assert::false($rc->isFinal());
Assert::false($rc->getMethod('finalMethod')->isFinal());
Assert::same(123, FinalReadonlyClass::FINAL);
Assert::same(456, (new FinalReadonlyClass)->final());
