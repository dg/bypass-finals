<?php

declare(strict_types=1);

namespace DG;


/**
 * Removes keyword final from source codes.
 */
class BypassFinals
{
	private const PROTOCOL = 'file';

	/** @var resource|null */
	public $context;

	/** @var object|null */
	private $wrapper;

	/** @var array */
	private static $pathWhitelist = ['*'];

	/** @var string */
	private static $underlyingWrapperClass;

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
		if ($wrapper instanceof self) {
			return;
		}

		self::$underlyingWrapperClass = $wrapper
			? get_class($wrapper)
			: NativeWrapper::class;
		NativeWrapper::$outerWrapper = self::class;
		stream_wrapper_unregister(self::PROTOCOL);
		stream_wrapper_register(self::PROTOCOL, self::class);
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


	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		$this->wrapper = $this->createUnderlyingWrapper();
		if (!$this->wrapper->stream_open($path, $mode, $options, $openedPath)) {
			return false;
		}

		if ($mode === 'rb' && pathinfo($path, PATHINFO_EXTENSION) === 'php' && self::isPathInWhiteList($path)) {
			$content = '';
			while (!$this->wrapper->stream_eof()) {
				$content .= $this->wrapper->stream_read(8192);
			}

			$modified = self::modifyCode($content);
			if ($modified === $content) {
				$this->wrapper->stream_seek(0);
			} else {
				$this->wrapper->stream_close();
				$this->wrapper = new NativeWrapper;
				$this->wrapper->handle = tmpfile();
				$this->wrapper->stream_write($modified);
				$this->wrapper->stream_seek(0);
			}
		}

		return true;
	}


	public function dir_opendir(string $path, int $options): bool
	{
		$this->wrapper = $this->createUnderlyingWrapper();
		return $this->wrapper->dir_opendir($path, $options);
	}


	private static function modifyCode(string $code): string
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


	private static function isPathInWhiteList(string $path): bool
	{
		$path = strtr($path, '\\', '/');
		foreach (self::$pathWhitelist as $mask) {
			if (fnmatch($mask, $path)) {
				return true;
			}
		}

		return false;
	}


	/** @return object */
	private function createUnderlyingWrapper()
	{
		$wrapper = new self::$underlyingWrapperClass;
		$wrapper->context = $this->context;
		return $wrapper;
	}


	/** @return mixed */
	public function __call(string $method, array $args)
	{
		$wrapper = $this->wrapper ?? $this->createUnderlyingWrapper();
		return method_exists($wrapper, $method)
			? $wrapper->$method(...$args)
			: false;
	}
}
