<?php declare(strict_types=1);

// test that wasFinal() refuses to answer when the source changed after loading

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$file = getTempDir() . '/changed.php';
file_put_contents($file, "<?php\nfinal class ChangedClass {}\nfinal class UnchangedClass {}\n");

BypassFinals::enable();
require $file;

file_put_contents($file, "<?php\nclass ChangedClass {}\nfinal class UnchangedClass {}\n");

Assert::exception(
	fn() => BypassFinals::wasFinal(ChangedClass::class),
	RuntimeException::class,
	"File '%a%changed.php' has changed since class ChangedClass was loaded.",
);
