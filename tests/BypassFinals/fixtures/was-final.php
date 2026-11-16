<?php declare(strict_types=1);

namespace WasFinal;

final class FinalClass
{
}

class RegularClass
{
}

#[Marker]
/** doc comment */
final
readonly class MultiLineClass
{
}

abstract class AbstractClass
{
	final public function method(): void
	{
	}
}

final class SameLineFinal {} class SameLineRegular {}
