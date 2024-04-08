<?php

/**
 * @see https://github.com/dg/bypass-finals/issues/52
 * @see https://www.php.net/manual/en/language.operators.errorcontrol.php
 *
 * As certain testing frameworks establish error handlers to pick up
 * on suppressed errors, exceptions and warnings, the warning generated
 * by `stat`/`lstat` when provided an unknown path is recorded.
 * 
 * `nette/tester` does _not_ detect suppressed warnings as the error handler
 * is set right before executing the closure.
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable();

function thoroughErrorHandler(int $number, string $message, string $file, int $line): bool
{
	/**
	 * NOTE: The use of the `@` operator sets the error level to
	 *       `E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE`
	 *       or 0 prior to PHP 8.0.0.
	 *       Resetting the error level to `E_ALL | E_STRICT` lets the previous error handler detect
	 *       suppressed errors, exceptions or warnings.
	 */
	error_reporting(E_ALL | E_STRICT);

	/**
	 * NOTE: Acquire the previous error handler from the stack and forward the error.
	 *       Restore the error handler stack afterwards. 
	 */
	restore_error_handler();
	$errorHandler = set_error_handler(null);
	$errorHandler($number, $message, $file, $line);

	return true;
}

Assert::noError(function () {
	set_error_handler('thoroughErrorHandler');

	is_dir('unknown');

	restore_error_handler();
});
