<?php declare(strict_types=1);

namespace DG;

use DG\BypassFinals\MutatingWrapper;
use DG\BypassFinals\NativeWrapper;

/**
 * Removes keyword 'final' & 'readonly' from source codes on-the-fly.
 */
final class BypassFinals
{
	private const IsWindows = PHP_OS_FAMILY === 'Windows';

	/** Bump to invalidate existing caches whenever the token-removal algorithm changes */
	private const CacheVersion = 1;

	/** @var array<int, list<string>> Access rules for allowing or denying paths */
	private static array $accessRules = [];

	/** Directory to store cached modified code */
	private static ?string $cacheDir = null;

	/** @var array<int, string> Tokens that represent 'readonly' and 'final' keywords */
	private static array $tokens = [];

	/** @var list<array{function: string, line?: int, file?: string, class?: string, type?: string}> Call stack when enable() was called */
	private static array $enableCallStack = [];

	/** @var array<string>  List of userland classes loaded before enable() was called */
	private static array $classesLoadedBeforeEnable = [];

	/** @var array<string>  List of files that were modified */
	private static array $modifiedFiles = [];


	/**
	 * Enables modification of the source code to bypass 'readonly' and 'final' restrictions.
	 */
	public static function enable(bool $bypassReadOnly = true, bool $bypassFinal = true): void
	{
		if (!self::$enableCallStack) {
			self::$enableCallStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			self::$classesLoadedBeforeEnable = array_filter(get_declared_classes(), fn(string $class): bool => !(new \ReflectionClass($class))->isInternal() && $class !== self::class);
		}

		if ($bypassReadOnly) {
			self::$tokens[T_READONLY] = 'readonly';
		}
		if ($bypassFinal) {
			self::$tokens[T_FINAL] = 'final';
		}

		// Check if a custom stream wrapper is already in use
		$handle = fopen(__FILE__, 'r') ?: throw new \RuntimeException('Unable to open ' . __FILE__);
		$wrapper = stream_get_meta_data($handle)['wrapper_data'] ?? null;
		if ($wrapper instanceof MutatingWrapper) {
			return;
		}

		// Set up the custom stream wrapper for code modification
		MutatingWrapper::$underlyingWrapperClass = $wrapper
			? $wrapper::class
			: NativeWrapper::class;
		stream_wrapper_unregister(NativeWrapper::Protocol);
		stream_wrapper_register(NativeWrapper::Protocol, MutatingWrapper::class);
	}


	/**
	 * @param  string[]  $masks
	 * @deprecated use BypassFinals::allowPaths()
	 */
	public static function setWhitelist(array $masks): void
	{
		self::$accessRules[true] = [];
		self::allowPaths($masks);
	}


	/**
	 * Sets the list of file path masks that are allowed for code modification.
	 * @param  string[]  $masks
	 */
	public static function allowPaths(array $masks): void
	{
		foreach ($masks as $mask) {
			self::$accessRules[true][] = strtr($mask, '\\', '/');
		}
	}


	/**
	 * Sets the list of file path masks that are denied for code modification.
	 * @param  string[]  $masks
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
					if ($file !== null) {
						self::$modifiedFiles[] = $file;
					}
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
		$cacheDir = self::$cacheDir ?? throw new \LogicException('Cache directory is not set.');
		$tokens = self::$tokens;
		ksort($tokens);
		$hash = sha1(serialize([$code, $tokens, self::CacheVersion, PHP_MAJOR_VERSION, PHP_MINOR_VERSION]));
		$file = $cacheDir . '/' . $hash;

		// All cache I/O in a single native context to avoid multiple stream_wrapper_restore
		// cycles inside an already-active MutatingWrapper stream_open callback, which
		// corrupts PHP's internal stream wrapper state.
		stream_wrapper_restore(NativeWrapper::Protocol);
		try {
			$cached = @file_get_contents($file); // @ may not exist
			if ($cached) {
				return $cached;
			}

			$code = self::removeTokens($code);

			// atomic write via rename() so a killed process cannot leave a truncated cache file behind
			@mkdir($cacheDir, 0o777, true); // @ may already exist
			$tmp = $file . '.' . uniqid('', true) . '.tmp';
			if (@file_put_contents($tmp, $code) !== strlen($code) // @ dir may be unwritable
				|| !@rename($tmp, $file) // @ target may exist (a concurrent writer won with identical content)
			) {
				@unlink($tmp); // @ may not exist
			}

			return $code;
		} finally {
			stream_wrapper_unregister(NativeWrapper::Protocol);
			stream_wrapper_register(NativeWrapper::Protocol, MutatingWrapper::class);
		}
	}


	/**
	 * Removes specified tokens from the code without caching.
	 * @internal
	 */
	public static function removeTokens(string $code): string
	{
		try {
			$tokens = token_get_all($code, TOKEN_PARSE);
		} catch (\CompileError) { // also covers ParseError
			return $code;
		}

		$code = '';
		foreach ($tokens as $i => $token) {
			if (!is_array($token)) {
				$code .= $token;
			} elseif ($token[0] === T_FINAL && isset(self::$tokens[T_FINAL])) {
				$code .= self::stripFinal($tokens, $i);
			} elseif ($token[0] === T_READONLY && isset(self::$tokens[T_READONLY])) {
				$code .= self::stripReadonly($tokens, $i);
			} else {
				$code .= $token[1];
			}
		}

		return $code;
	}


	/**
	 * 'final' is kept for constants, dropped before classes, methods and property hooks (PHP 8.4+),
	 * and dropped before properties (PHP 8.4+) unless it is their only modifier, in which case
	 * it becomes 'public' because a property cannot be declared without any modifier.
	 * @param  list<string|array{int, string, int}>  $tokens
	 */
	private static function stripFinal(array $tokens, int $i): string
	{
		$next = self::significantToken($tokens, $i, 1, self::getModifierIds());
		return match (true) {
			is_array($next) && $next[0] === T_CONST => 'final', // final constants are not a mockability barrier
			is_array($next) && in_array($next[0], [T_CLASS, T_FUNCTION], true) => '',
			self::modifiesHook($tokens, $i) => '',
			default => self::hasOtherModifiers($tokens, $i) ? '' : 'public',
		};
	}


	/**
	 * 'readonly' is dropped before a class and before a property with another modifier; when it is
	 * the only modifier, it becomes 'public' so the declaration stays valid and a visibility-less
	 * promoted "readonly T $x" stays a promoted property.
	 * @param  list<string|array{int, string, int}>  $tokens
	 */
	private static function stripReadonly(array $tokens, int $i): string
	{
		$next = self::significantToken($tokens, $i, 1, self::getModifierIds());
		return (is_array($next) && $next[0] === T_CLASS) || self::hasOtherModifiers($tokens, $i)
			? ''
			: 'public';
	}


	/**
	 * Determines whether the property declaration keeps at least one modifier when the one at $i
	 * is removed: either a preceding modifier handles it (a kept one stays, a removed one emits
	 * 'public' itself), or one of the following modifiers survives the removal.
	 * @param  list<string|array{int, string, int}>  $tokens
	 */
	private static function hasOtherModifiers(array $tokens, int $i): bool
	{
		$prev = self::significantToken($tokens, $i, -1);
		if (is_array($prev) && in_array($prev[0], self::getModifierIds(), true)) {
			return true;
		}

		for ($j = $i + 1; isset($tokens[$j]); $j++) {
			$t = $tokens[$j];
			if (!is_array($t)) {
				break;
			} elseif (in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			} elseif (!in_array($t[0], self::getModifierIds(), true)) {
				break;
			} elseif (!isset(self::$tokens[$t[0]])) {
				return true; // this modifier survives the removal
			}
		}

		return false;
	}


	/**
	 * Determines whether the 'final' at $i modifies a property hook (PHP 8.4+), i.e. is followed
	 * by get/set (possibly by-ref) rather than by a property typed with a class named get/set.
	 * @param  list<string|array{int, string, int}>  $tokens
	 */
	private static function modifiesHook(array $tokens, int $i): bool
	{
		$hookName = false;
		for ($j = $i + 1; isset($tokens[$j]); $j++) {
			$t = $tokens[$j];
			if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
				continue;
			} elseif ($hookName) {
				return $t === '{' || $t === '(' || $t === ';' || (is_array($t) && $t[0] === T_DOUBLE_ARROW);
			} elseif (is_array($t) && $t[0] === T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG) {
				return true; // by-ref hook "final &get"
			} elseif (is_array($t) && $t[0] === T_STRING && in_array(strtolower($t[1]), ['get', 'set'], true)) {
				$hookName = true;
			} else {
				return false;
			}
		}

		return false;
	}


	/**
	 * Returns all tokens that can act as class member modifiers.
	 * @return list<int>
	 */
	private static function getModifierIds(): array
	{
		static $ids;
		if ($ids === null) {
			$ids = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_READONLY, T_FINAL];
			foreach (['T_PUBLIC_SET', 'T_PROTECTED_SET', 'T_PRIVATE_SET'] as $name) { // PHP 8.4+
				if (defined($name)) {
					$ids[] = (int) constant($name);
				}
			}
		}

		return $ids;
	}


	/**
	 * Returns the nearest token in the given direction (+1/-1) that is not whitespace, a comment
	 * or one of the additionally skipped token types.
	 * @return string|array{int, string, int}|null
	 * @param  list<string|array{int, string, int}>  $tokens
	 * @param  list<int>  $skip
	 */
	private static function significantToken(array $tokens, int $i, int $direction, array $skip = [])
	{
		$ignore = array_merge([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], $skip);
		for ($j = $i + $direction; isset($tokens[$j]); $j += $direction) {
			$t = $tokens[$j];
			if (is_array($t) && in_array($t[0], $ignore, true)) {
				continue;
			}
			return $t;
		}

		return null;
	}


	/**
	 * Determines if a given path is allowed for code modification based on the configured rules.
	 * @internal
	 */
	public static function isPathAllowed(string $path): bool
	{
		$path = strtr($path, '\\', '/');
		$flags = self::IsWindows ? FNM_CASEFOLD : 0; // Windows paths are case-insensitive
		foreach (self::$accessRules[true] ?? ['*'] as $mask) {
			if (fnmatch($mask, $path, $flags)) {
				foreach (self::$accessRules[false] ?? [] as $mask) {
					if (fnmatch($mask, $path, $flags)) {
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
		echo "  Bypass 'final': " . (isset(self::$tokens[T_FINAL]) ? 'enabled' : 'disabled') . "\n";
		echo "  Bypass 'readonly': " . (isset(self::$tokens[T_READONLY]) ? 'enabled' : 'disabled') . "\n";
		echo '  Allowed paths: ' . (isset(self::$accessRules[true]) ? implode(', ', self::$accessRules[true]) : '* (all)') . "\n";
		echo '  Denied paths: ' . (isset(self::$accessRules[false]) ? implode(', ', self::$accessRules[false]) : 'none') . "\n";
		echo '  Cache directory: ' . (self::$cacheDir ?? 'not set') . "\n";

		echo "\nFrom where BypassFinals::enable() was started:\n";
		foreach (self::$enableCallStack as $index => $frame) {
			echo "  #$index ";
			if (isset($frame['class'], $frame['type'])) {
				echo $frame['class'] . $frame['type'] . $frame['function'] . '()';
			} else {
				echo $frame['function'] . '()';
			}
			if (isset($frame['file'], $frame['line'])) {
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
		echo "</xmp>\n";
	}
}
