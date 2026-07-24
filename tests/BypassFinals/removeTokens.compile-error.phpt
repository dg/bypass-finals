<?php declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


DG\BypassFinals::enable();

// TOKEN_PARSE raises CompileError (not ParseError) for code that parses but breaks
// compile-time modifier rules; removeTokens() must leave such code untouched
$originalCode = '<?php abstract final class CompileErrorFixture {}';

Assert::noError(
	fn() => Assert::same($originalCode, DG\BypassFinals::removeTokens($originalCode)),
);
