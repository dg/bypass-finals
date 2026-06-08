<?php declare(strict_types=1);

/** @phpVersion 8.1 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: false);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	final class ReadonlyBeforeVisibility
	{
		public function __construct(
			readonly protected string $a,
			readonly public string $b,
		) {
		}
	}
	XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	final class ReadonlyBeforeVisibility
	{
		public function __construct(
			 protected string $a,
			 public string $b,
		) {
		}
	}
	XX, $modifiedCode);
