<?php declare(strict_types=1);

#[Attribute]
final class MyTestDepends
{
	public function __construct(public readonly string $method)
	{
	}
}

final class AttributedClass
{
	public function testFirst(): void
	{
	}

	#[MyTestDepends('testFirst')]
	public function testSecond(): void
	{
	}
}
