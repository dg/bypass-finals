<?php declare(strict_types=1);

/** @phpVersion 8.4 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: false, bypassFinal: true);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	class FinalHooks
	{
		public int $a {
			final get => 42;
			final set($value) {
			}
		}

		public int $b {
			final &get {
				return $this->b;
			}
		}

		final public int $c {
			get => 42;
		}
	}
	XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	class FinalHooks
	{
		public int $a {
			 get => 42;
			 set($value) {
			}
		}

		public int $b {
			 &get {
				return $this->b;
			}
		}

		 public int $c {
			get => 42;
		}
	}
	XX, $modifiedCode);

Assert::noError(fn() => token_get_all($modifiedCode, TOKEN_PARSE));
