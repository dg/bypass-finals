<?php declare(strict_types=1);

/** @phpVersion 8.4 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: true);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	class FinalReadonlyProperties
	{
		final readonly int $a;
		readonly final int $b;
		public final readonly int $c;
	}
	XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	class FinalReadonlyProperties
	{
		public  int $a;
		public  int $b;
		public   int $c;
	}
	XX, $modifiedCode);

Assert::noError(fn() => token_get_all($modifiedCode, TOKEN_PARSE));
