<?php declare(strict_types=1);

/** @phpVersion 8.4 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable();

require __DIR__ . '/fixtures/final.property.php';

$rc = new ReflectionClass('ClassWithFinalProperties');
Assert::false($rc->getProperty('a')->isFinal());
Assert::false($rc->getProperty('b')->isFinal());
Assert::false($rc->getProperty('c')->getHook(PropertyHookType::Get)->isFinal());
Assert::same(42, (new ClassWithFinalProperties)->c);
