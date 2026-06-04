<?php declare(strict_types=1);

/** @phpVersion 8.1 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: false);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	final class ReadonlyNoVisibility
	{
		public function __construct(
			readonly string $a,
			public readonly string $b,
		) {
		}
	}
	XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	final class ReadonlyNoVisibility
	{
		public function __construct(
			public string $a,
			public  string $b,
		) {
		}
	}
	XX, $modifiedCode);
