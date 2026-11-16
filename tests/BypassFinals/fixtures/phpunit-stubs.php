<?php declare(strict_types=1);

// Minimal stand-ins for the PHPUnit types used by PHPUnitExtension, so it can be tested without PHPUnit.

namespace PHPUnit\Runner\Extension;

use PHPUnit\TextUI\Configuration\Configuration;

interface Extension
{
	public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void;
}

final class Facade
{
}

final class ParameterCollection
{
	/** @param  array<string, string>  $parameters */
	public function __construct(
		private array $parameters,
	) {
	}


	public function has(string $name): bool
	{
		return isset($this->parameters[$name]);
	}


	public function get(string $name): string
	{
		return $this->parameters[$name];
	}
}


namespace PHPUnit\TextUI\Configuration;

final class Configuration
{
}
