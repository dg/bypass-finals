<?php declare(strict_types=1);

final class FinalClass
{
	const FINAL = 123;
	const X = self::FINAL;

	final function finalMethod()
	{
	}

	function final ()
	{
		return 456;
	}
}
