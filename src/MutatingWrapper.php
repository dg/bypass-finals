<?php

declare(strict_types=1);

namespace DG\BypassFinals;

use DG\BypassFinals;


/**
 * Wrapper that mutates PHP source codes.
 * @internal
 */
final class MutatingWrapper
{
	/** @var string */
	public static $underlyingWrapperClass;

	/** @var resource|null */
	public $context;

	/** @var object|null */
	private $wrapper;


	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		$this->wrapper = $this->createUnderlyingWrapper();
		if (!$this->wrapper->stream_open($path, $mode, $options, $openedPath)) {
			return false;
		}

		if ($mode === 'rb' && pathinfo($path, PATHINFO_EXTENSION) === 'php' && BypassFinals::isPathInWhiteList($path)) {
			$content = '';
			while (!$this->wrapper->stream_eof()) {
				$content .= $this->wrapper->stream_read(8192);
			}

			$modified = BypassFinals::modifyCode($content);
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
