<?php declare(strict_types=1);

// test PHPUnitExtension boolean parameters and cache directory

use DG\BypassFinals;
use PHPUnit\Runner\Extension\{Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/phpunit-stubs.php';


$bootstrap = fn(array $parameters) => (new BypassFinals\PHPUnitExtension)
	->bootstrap(new Configuration, new Facade, new ParameterCollection($parameters));

Assert::exception(
	fn() => $bootstrap(['bypassFinal' => 'maybe']),
	InvalidArgumentException::class,
	'Invalid boolean-like value: maybe',
);

$cacheDir = getTempDir() . '/cache';
$bootstrap([
	'bypassFinal' => 'Yes',
	'bypassReadOnly' => '0',
	'cacheDirectory' => $cacheDir,
]);

ob_start();
BypassFinals::debugInfo();
$info = ob_get_clean();

Assert::contains("Bypass 'final': enabled", $info);
Assert::contains("Bypass 'readonly': disabled", $info);
Assert::contains("Cache directory: $cacheDir", $info);
