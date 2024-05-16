<?php

// test that overloaded "exists-check" functions (see php-src/ext/standard/filestat.c) must not invoke the error handler

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable();


function thoroughErrorHandler(): void
{
	throw new Exception('This must not happen');
}


Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	file_exists('unknown');

	restore_error_handler();
});

Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	is_writable('unknown');

	restore_error_handler();
});

Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	is_readable('unknown');

	restore_error_handler();
});

Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	is_executable('unknown');

	restore_error_handler();
});

Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	is_file('unknown');

	restore_error_handler();
});

Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	is_dir('unknown');

	restore_error_handler();
});

Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	is_link('unknown');

	restore_error_handler();
});
