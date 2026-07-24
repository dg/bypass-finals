<?php declare(strict_types=1);

class ClassWithFinalProperties
{
	final public int $a = 1;

	public int $c {
		final get => 42;
	}

	final protected int $b = 2;
}
