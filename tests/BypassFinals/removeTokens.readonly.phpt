<?php

/** @phpVersion 8.2 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


// Enable only readonly bypassing
DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: false);

$originalCode = file_get_contents(__DIR__ . '/fixtures/final.readonly.class.php');

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
<?php
declare(strict_types=1);

final  class FinalReadonlyClass
{
	const FINAL = 123;
	public  int $foo;

	final function finalMethod()
	{
	}

	function final ()
	{
		return 456;
	}
}

XX, $modifiedCode);
