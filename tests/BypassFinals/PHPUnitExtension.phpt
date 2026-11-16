<?php declare(strict_types=1);

// test PHPUnitExtension path rules

use DG\BypassFinals;
use PHPUnit\Runner\Extension\{Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/phpunit-stubs.php';


(new BypassFinals\PHPUnitExtension)->bootstrap(new Configuration, new Facade, new ParameterCollection([
	'allowPaths' => '*/app/*',
	'denyPaths' => '*/vendor/*; !*/vendor/*/src/*',
]));

Assert::true(BypassFinals::isPathAllowed('/app/src/Foo.php'));
Assert::false(BypassFinals::isPathAllowed('/other/src/Foo.php'));
Assert::false(BypassFinals::isPathAllowed('/app/vendor/acme/lib/Foo.php'));
Assert::true(BypassFinals::isPathAllowed('/app/vendor/acme/lib/src/Foo.php'));

// the exception from phpunit.xml does not re-enable modifying PHPUnit itself
Assert::false(BypassFinals::isPathAllowed('/app/vendor/phpunit/phpunit/src/Framework/TestCase.php'));
