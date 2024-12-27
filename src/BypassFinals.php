<?php

declare(strict_types=1);

namespace DG;

use DG\BypassFinals\MutatingWrapper;
use DG\BypassFinals\NativeWrapper;


/**
 * Removes keyword 'final' & 'readonly' from source codes on-the-fly.
 */
final class BypassFinals
{
	/** @var array  Access rules for allowing or denying paths */
	private static $accessRules = [];

	/** @var ?string  Directory to store cached modified code */
	private static $cacheDir;

	/** @var array  Tokens that represent 'readonly' and 'final' keywords */
	private static $tokens = [];

	/** @var array<int, array{file: string, line: int, function: string, class?: string, type?: string, args?: array}> Call stack when enable() was called */
	private static $enableCallStack = [];

	/** @var array<string>  List of userland classes loaded before enable() was called */
	private static $classesLoadedBeforeEnable = [];

	/** @var array<string>  List of files that were modified */
	private static $modifiedFiles = [];


	/**
	 * Enables modification of the source code to bypass 'readonly' and 'final' restrictions.
	 */
	public static function enable(bool $bypassReadOnly = true, bool $bypassFinal = true): void
	{
		if (!self::$enableCallStack) {
			self::$enableCallStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			self::$classesLoadedBeforeEnable = array_filter(get_declared_classes(), function (string $class): bool {
				return !(new \ReflectionClass($class))->isInternal() && $class !== self::class;
			});
		}

		if ($bypassReadOnly && PHP_VERSION_ID >= 80100) {
			self::$tokens[T_READONLY] = 'readonly';
		}
		if ($bypassFinal) {
			self::$tokens[T_FINAL] = 'final';
		}

		// Check if a custom stream wrapper is already in use
		$wrapper = stream_get_meta_data(fopen(__FILE__, 'r'))['wrapper_data'] ?? null;
		if ($wrapper instanceof MutatingWrapper) {
			return;
		}

		// Set up the custom stream wrapper for code modification
		MutatingWrapper::$underlyingWrapperClass = $wrapper
			? get_class($wrapper)
			: NativeWrapper::class;
		stream_wrapper_unregister(NativeWrapper::Protocol);
		stream_wrapper_register(NativeWrapper::Protocol, MutatingWrapper::class);
	}


	/** @deprecated use BypassFinals::allowPaths() */
	public static function setWhitelist(array $masks): void
	{
		self::$accessRules[true] = [];
		self::allowPaths($masks);
	}


	/**
	 * Sets the list of file path masks that are allowed for code modification.
	 */
	public static function allowPaths(array $masks): void
	{
		foreach ($masks as $mask) {
			self::$accessRules[true][] = strtr($mask, '\\', '/');
		}
	}


	/**
	 * Sets the list of file path masks that are denied for code modification.
	 */
	public static function denyPaths(array $masks): void
	{
		foreach ($masks as $mask) {
			self::$accessRules[false][] = strtr($mask, '\\', '/');
		}
	}


	/**
	 * Sets the directory where modified code should be cached.
	 */
	public static function setCacheDirectory(?string $dir): void
	{
		self::$cacheDir = $dir;
	}


	/**
	 * Modifies the PHP code by removing specified tokens if they exist.
	 * @internal
	 */
	public static function modifyCode(string $code, ?string $file = null): string
	{
		foreach (self::$tokens as $text) {
			if (stripos($code, $text) !== false) {
				$modifiedCode = self::$cacheDir
					? self::removeTokensCached($code)
					: self::removeTokens($code);
				if ($modifiedCode !== $code) {
					self::$modifiedFiles[] = $file;
					return $modifiedCode;
				}
				return $code;
			}
		}

		return $code;
	}


	/**
	 * Removes specified tokens from the code and caches the result.
	 */
	private static function removeTokensCached(string $code): string
	{
		$wrapper = new NativeWrapper;
		$hash = sha1($code . implode(',', self::$tokens));
		if (@$wrapper->stream_open(self::$cacheDir . '/' . $hash, 'r')) { // @ may not exist
			flock($wrapper->handle, LOCK_SH);
			if ($res = stream_get_contents($wrapper->handle)) {
				return $res;
			}
		}

		$code = self::removeTokens($code);

		if (@$wrapper->stream_open(self::$cacheDir . '/' . $hash, 'x')) { // @ may exist
			flock($wrapper->handle, LOCK_EX);
			fwrite($wrapper->handle, $code);
		}

		return $code;
	}


	/**
	 * Removes specified tokens from the code without caching.
	 */
	private static function removeTokens(string $code): string
	{
		try {
			$tokens = token_get_all($code, TOKEN_PARSE);
		} catch (\ParseError $e) {
			return $code;
		}

		$code = '';
		foreach ($tokens as $token) {
			$code .= is_array($token)
				? (isset(self::$tokens[$token[0]]) ? '' : $token[1])
				: $token;
		}

		return $code;
	}


	/**
	 * Determines if a given path is allowed for code modification based on the configured rules.
	 * @internal
	 */
	public static function isPathAllowed(string $path): bool
	{
		$path = strtr($path, '\\', '/');
		foreach (self::$accessRules[true] ?? ['*'] as $mask) {
			if (fnmatch($mask, $path)) {
				foreach (self::$accessRules[false] ?? [] as $mask) {
					if (fnmatch($mask, $path)) {
						return false;
					}
				}

				return true;
			}
		}

		return false;
	}


	/**
	 * Returns debugging information to help diagnose issues.
	 */
	public static function debugInfo(): void
	{
		echo "<xmp>\n";
		echo "BypassFinals Debug Information\n";
		echo "------------------------------\n\n";
		echo "Configuration:\n";
		echo "  Bypass 'final': " . (PHP_VERSION_ID >= 80100 && isset(self::$tokens[T_READONLY]) ? 'enabled' : 'disabled') . "\n";
		echo "  Bypass 'readonly': " . (isset(self::$tokens[T_FINAL]) ? 'enabled' : 'disabled') . "\n";

		echo "\nFrom where BypassFinals::enable() was started:\n";
		foreach (self::$enableCallStack as $index => $frame) {
			echo "  #$index ";
			if (isset($frame['class'])) {
				echo $frame['class'] . $frame['type'] . $frame['function'] . '()';
			} elseif (isset($frame['function'])) {
				echo $frame['function'] . '()';
			}
			if (isset($frame['file'])) {
				echo ' in ' . $frame['file'] . ':' . $frame['line'];
			}
			echo "\n";
		}

		echo "\nClasses already loaded before BypassFinals was started:\n";
		if (self::$classesLoadedBeforeEnable) {
			foreach (self::$classesLoadedBeforeEnable as $class) {
				echo "  - $class\n";
			}
		} else {
			echo "  no classes\n";
		}

		echo "\nFiles where BypassFinals removed final/readonly:\n";
		if (self::$modifiedFiles) {
			foreach (self::$modifiedFiles as $file) {
				echo "  - $file\n";
			}
		} else {
			echo "  no files were modified\n";
		}
	}
}
