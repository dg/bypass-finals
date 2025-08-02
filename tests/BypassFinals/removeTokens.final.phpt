<?php

/** @phpVersion 8.2 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


// Enable only final bypassing
DG\BypassFinals::enable(bypassReadOnly: false, bypassFinal: true);

$originalCode = file_get_contents(__DIR__ . '/fixtures/final.class.php');

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
<?php
declare(strict_types=1);

 class FinalClass
{
	const FINAL = 123;

	 function finalMethod()
	{
	}

	function final ()
	{
		return 456;
	}
}

XX, $modifiedCode);
