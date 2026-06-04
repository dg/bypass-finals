<?php declare(strict_types=1);

/** @phpVersion 8.4 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: false);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	final class ReadonlyAsymmetric
	{
		public function __construct(
			public private(set) readonly string $a,
		) {
		}
	}
	XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	final class ReadonlyAsymmetric
	{
		public function __construct(
			public private(set)  string $a,
		) {
		}
	}
	XX, $modifiedCode);
