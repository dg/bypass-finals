<?php declare(strict_types=1);

/** @phpVersion 8.2 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Enable both final and readonly bypassing
DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: true);

$originalCode = file_get_contents(__DIR__ . '/fixtures/final.readonly.class.php');

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	  class FinalReadonlyClass
	{
		const FINAL = 123;
		const X = self::FINAL;
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
