<?php declare(strict_types=1);

namespace DG\BypassFinals;

use DG\BypassFinals;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;


final class PHPUnitExtension implements Extension
{
	public function bootstrap(
		Configuration $configuration,
		Facade $facade,
		ParameterCollection $parameters,
	): void
	{
		BypassFinals::denyPaths([
			'*/vendor/phpunit/*',
		]);

		if ($parameters->has('allowPaths')) {
			BypassFinals::allowPaths($this->parsePathList($parameters->get('allowPaths')));
		}

		if ($parameters->has('denyPaths')) {
			BypassFinals::denyPaths($this->parsePathList($parameters->get('denyPaths')));
		}

		$bypassReadOnly = !$parameters->has('bypassReadOnly') || $this->parseBoolean($parameters->get('bypassReadOnly'));
		$bypassFinal = !$parameters->has('bypassFinal') || $this->parseBoolean($parameters->get('bypassFinal'));
		BypassFinals::enable($bypassReadOnly, $bypassFinal);

		if ($parameters->has('cacheDirectory')) {
			BypassFinals::setCacheDirectory($parameters->get('cacheDirectory'));
		}
	}


	/** @return list<string> */
	private function parsePathList(string $value): array
	{
		return array_values(array_filter(array_map(trim(...), explode(';', $value))));
	}


	private function parseBoolean(string $value): bool
	{
		$value = strtolower($value);
		if (in_array($value, ['1', 'true', 'yes'], true)) {
			return true;
		} elseif (in_array($value, ['0', 'false', 'no'], true)) {
			return false;
		} else {
			throw new \InvalidArgumentException("Invalid boolean-like value: $value");
		}
	}
}
