<?php
declare(strict_types=1);

final readonly class FinalReadonlyClass
{
	const FINAL = 123;
	public readonly int $foo;

	final function finalMethod()
	{
	}

	function final ()
	{
		return 456;
	}
}
