<?php

declare(strict_types=1);

namespace DG;

use DG\BypassFinals\MutatingWrapper;
use DG\BypassFinals\NativeWrapper;


/**
 * Removes keyword final & readonly from source codes.
 */
final class BypassFinals
{
	/** @var array */
	private static $pathWhitelist = ['*'];

	/** @var ?string */
	private static $cacheDir;

	/** @var array */
	private static $tokens = [];


	public static function enable(bool $bypassReadOnly = true, bool $bypassFinal = true): void
	{
		if ($bypassReadOnly && PHP_VERSION_ID >= 80100) {
			self::$tokens[T_READONLY] = 'readonly';
		}
		if ($bypassFinal) {
			self::$tokens[T_FINAL] = 'final';
		}

		$wrapper = stream_get_meta_data(fopen(__FILE__, 'r'))['wrapper_data'] ?? null;
		if ($wrapper instanceof MutatingWrapper) {
			return;
		}

		MutatingWrapper::$underlyingWrapperClass = $wrapper
			? get_class($wrapper)
			: NativeWrapper::class;
		stream_wrapper_unregister(NativeWrapper::Protocol);
		stream_wrapper_register(NativeWrapper::Protocol, MutatingWrapper::class);
	}


	public static function setWhitelist(array $whitelist): void
	{
		foreach ($whitelist as &$mask) {
			$mask = strtr($mask, '\\', '/');
		}

		self::$pathWhitelist = $whitelist;
	}


	public static function setCacheDirectory(?string $dir): void
	{
		self::$cacheDir = $dir;
	}


	/** @internal */
	public static function modifyCode(string $code): string
	{
		foreach (self::$tokens as $text) {
			if (stripos($code, $text) !== false) {
				return self::$cacheDir
					? self::removeTokensCached($code)
					: self::removeTokens($code);

			}
		}

		return $code;
	}


	private static function removeTokensCached(string $code): string
	{
		$wrapper = new NativeWrapper;
		$hash = sha1($code);
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


	/** @internal */
	public static function isPathInWhiteList(string $path): bool
	{
		$path = strtr($path, '\\', '/');
		foreach (self::$pathWhitelist as $mask) {
			if (fnmatch($mask, $path)) {
				return true;
			}
		}

		return false;
	}
}
