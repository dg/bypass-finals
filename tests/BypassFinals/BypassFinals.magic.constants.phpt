<?php

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


DG\BypassFinals::enable();

require __DIR__ . '/fixtures/magic.constants.php';


$rc = new ReflectionClass('Foo');
Assert::false($rc->isFinal());

$res = getMagic();
Assert::same(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'magic.constants.php', $res[0]);
Assert::same(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures', $res[1]);
Assert::same(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures', $res[2]);
