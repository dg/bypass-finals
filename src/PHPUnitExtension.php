<?php

declare(strict_types=1);

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
		ParameterCollection $parameters
	): void
	{
		BypassFinals::denyPaths([
			'*/vendor/phpunit/*',
		]);
        BypassFinals::enable(
            bypassReadOnly: $this->shouldBypassReadonly($parameters) ?? true,
            bypassFinal: $this->shouldBypassFinal($parameters) ?? true,
        );

        if ($parameters->has('cacheDirectory')) {
            BypassFinals::setCacheDirectory($parameters->get('cacheDirectory'));
        }
    }

    private function shouldBypassReadonly(ParameterCollection $parameters): ?bool
    {
        if (! $parameters->has('bypassReadOnly')) {
            return null;
        }

        return $this->parseBoolean($parameters->get('bypassReadOnly'));
    }

    private function shouldBypassFinal(ParameterCollection $parameters): ?bool
    {
        if (! $parameters->has('bypassFinal')) {
            return null;
        }

        return $this->parseBoolean($parameters->get('bypassFinal'));
    }

    /**
     * Parse a boolean-like value safely.
     */
    private function parseBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower($value);

            if (in_array($value, ['1', 'true', 'yes'], true)) {
                return true;
            }

            if (in_array($value, ['0', 'false', 'no'], true)) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return null;
    }
}
