<?php declare(strict_types=1);

/** @phpVersion 8.0 */

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


@mkdir($dir = __DIR__ . '/cache');
DG\BypassFinals::setCacheDirectory($dir);

DG\BypassFinals::enable();

require __DIR__ . '/fixtures/final.attributes.class.php';

// Both methods must be discoverable (issue #55: cache caused one method to vanish)
$rc = new ReflectionClass('AttributedClass');
Assert::false($rc->isFinal());

$methods = array_map(fn($m) => $m->getName(), $rc->getMethods(ReflectionMethod::IS_PUBLIC));
Assert::contains('testFirst', $methods);
Assert::contains('testSecond', $methods);

// PHP attribute on testSecond must be intact after final-stripping via cache
$rm = new ReflectionMethod('AttributedClass', 'testSecond');
$attrs = $rm->getAttributes(MyTestDepends::class);
Assert::count(1, $attrs);
Assert::same('testFirst', $attrs[0]->newInstance()->method);
