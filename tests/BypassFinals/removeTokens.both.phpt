<?php

/** @phpVersion 8.2 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


// Enable both final and readonly bypassing
DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: true);

$originalCode = file_get_contents(__DIR__ . '/fixtures/final.readonly.class.php');

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
<?php
declare(strict_types=1);

  class FinalReadonlyClass
{
	const FINAL = 123;
	public  int $foo;

	 function finalMethod()
	{
	}

	function final ()
	{
		return 456;
	}
}

XX, $modifiedCode);
