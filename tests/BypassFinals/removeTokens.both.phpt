<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: true);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	final readonly class FinalReadonlyClass
	{
		const FINAL = 123;
		public readonly int $foo;

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
