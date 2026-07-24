<?php declare(strict_types=1);

/** @phpVersion 8.4 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: false, bypassFinal: true);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	class FinalProperties
	{
		final public int $a = 1;
		public final int $b = 2;
		final int $c = 3;
		final protected ?string $d = null;
		final public static array $e = [];
		final get $f;
	}
	XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	class FinalProperties
	{
		 public int $a = 1;
		public  int $b = 2;
		public int $c = 3;
		 protected ?string $d = null;
		 public static array $e = [];
		public get $f;
	}
	XX, $modifiedCode);

Assert::noError(fn() => token_get_all($modifiedCode, TOKEN_PARSE));
