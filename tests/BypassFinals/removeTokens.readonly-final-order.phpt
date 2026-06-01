<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable(bypassReadOnly: true, bypassFinal: true);

$originalCode = <<<'XX'
	<?php declare(strict_types=1);

	namespace App;

	readonly final class A {}
	final readonly class B {}
	readonly abstract class C {}
	abstract readonly class D {}
	XX;

$modifiedCode = DG\BypassFinals::removeTokens($originalCode);

Assert::match(<<<'XX'
	<?php declare(strict_types=1);

	namespace App;

	  class A {}
	  class B {}
	 abstract class C {}
	abstract  class D {}

	XX, $modifiedCode);

Assert::noError(fn() => token_get_all($modifiedCode, TOKEN_PARSE));
