<?php declare(strict_types=1);

/** @phpVersion 8.2 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Enable only final bypassing: 'final' is removed, 'readonly' is kept
DG\BypassFinals::enable(bypassReadOnly: false, bypassFinal: true);

$originalCode = file_get_contents(__DIR__ . '/fixtures/final.readonly.class.php');

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	 readonly class FinalReadonlyClass
	{
		const FINAL = 123;
		const X = self::FINAL;
		public readonly int $foo;

		 function finalMethod()
		{
		}

		function final ()
		{
			return 456;
		}
	}

	XX, $modifiedCode);
