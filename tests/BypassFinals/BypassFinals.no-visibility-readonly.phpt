<?php declare(strict_types=1);

/** @phpVersion 8.4 */

// Regression test for issue #49:
// "readonly T $x" without explicit visibility is valid PHP 8.4+ constructor promotion.
// Stripping readonly would turn it into a plain argument; it must become "public T $x" instead.

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();

require __DIR__ . '/fixtures/final.readonly.noinit.class.php';

$rc = new ReflectionClass('ReadonlyNoVisibility');
Assert::false($rc->isFinal());

$obj = new ReadonlyNoVisibility('hello', 'world');

// Both must be accessible as promoted properties, not lost as plain arguments
Assert::same('hello', $obj->a);
Assert::same('world', $obj->b);
