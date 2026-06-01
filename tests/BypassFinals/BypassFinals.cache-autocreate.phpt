<?php declare(strict_types=1);

// test that setCacheDirectory() creates missing directories recursively (issue #66)

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$dir = getTempDir() . '/nested/sub';

DG\BypassFinals::setCacheDirectory($dir);
DG\BypassFinals::enable();

require __DIR__ . '/fixtures/final.class.php';

Assert::true(is_dir($dir));

$rc = new ReflectionClass('FinalClass');
Assert::false($rc->isFinal());
