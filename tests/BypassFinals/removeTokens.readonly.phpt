<?php declare(strict_types=1);

/** @phpVersion 8.2 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: false);

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
