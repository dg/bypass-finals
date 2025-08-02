<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Enable only final bypassing: 'final' is removed, 'readonly' is kept
DG\BypassFinals::enable(false, true);

$originalCode = <<<'XX'
<?php declare(strict_types=1);

final class FinalClass
{
	const FINAL = 123;

	final function finalMethod()
	{
	}

	function final ()
	{
		return 456;
	}
}
XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
<?php declare(strict_types=1);

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

XX
, $modifiedCode);
