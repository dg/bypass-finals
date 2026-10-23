<?php declare(strict_types=1);

// test that a script already cached by opcache is not served past the wrapper

/**
 * @phpIni opcache.enable=1
 * @phpIni opcache.enable_cli=1
 * @phpIni opcache.file_update_protection=0
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (!function_exists('opcache_compile_file') || !opcache_get_status()) {
	Tester\Environment::skip('OPcache is not available.');
}


$file = __DIR__ . '/fixtures/final.opcache.php';
opcache_compile_file($file);
Assert::true(opcache_is_script_cached($file));

if (true) { // declared only now, so that the compilation above cannot bind the class to it
	class OpcacheParentClass
	{
	}
}

DG\BypassFinals::enable();

require $file;

Assert::false((new ReflectionClass('OpcacheFinalClass'))->isFinal());
