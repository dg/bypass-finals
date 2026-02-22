<?php declare(strict_types=1);

/** @phpVersion 8.2 */

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();

require __DIR__ . '/fixtures/final.readonly.class.php';

$rc = new ReflectionClass('FinalReadonlyClass');
Assert::false($rc->isReadOnly());
Assert::false($rc->isFinal());
Assert::false($rc->getMethod('finalMethod')->isFinal());
Assert::false($rc->getProperty('foo')->isReadOnly());
Assert::same(123, FinalReadonlyClass::FINAL);
Assert::same(456, (new FinalReadonlyClass)->final());
