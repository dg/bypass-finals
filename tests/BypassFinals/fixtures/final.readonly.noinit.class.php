<?php declare(strict_types=1);

final class ReadonlyNoVisibility
{
	public function __construct(
		readonly string $a,
		public readonly string $b,
	) {
	}
}
