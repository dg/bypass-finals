<?php

declare(strict_types=1);

namespace DG\BypassFinals;

use DG\BypassFinals;


/**
 * A stream wrapper class that mutates PHP source code by modifying 'final' and 'readonly' keywords.
 * @internal
 */
final class MutatingWrapper
{
	/** @var string  Specifies the class of the underlying normal wrapper */
	public static $underlyingWrapperClass;

	/** @var resource|null  Stream context, which may be set by stream functions */
	public $context;

	/** @var object|null  Instance of the actual underlying wrapper used for file operations */
	private $wrapper;


	/**
	 * Opens a stream resource and creates $wrapper property. It can modify PHP source files if allowed by the path rules.
	 */
	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		if (is_dir($path)) {
			return false;
		}

		$this->wrapper = $this->createUnderlyingWrapper();
		if (!$this->wrapper->stream_open($path, $mode, $options, $openedPath)) {
			return false;
		}

		if ($mode === 'rb' && pathinfo($path, PATHINFO_EXTENSION) === 'php' && BypassFinals::isPathAllowed($path)) {
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


	/**
	 * Delegates the handling of directory opening to the underlying wrapper and creates $wrapper property.
	 */
	public function dir_opendir(string $path, int $options): bool
	{
		$this->wrapper = $this->createUnderlyingWrapper();
		return $this->wrapper->dir_opendir($path, $options);
	}


	/**
	 * Instantiates the underlying wrapper.
	 */
	private function createUnderlyingWrapper()
	{
		$wrapper = new self::$underlyingWrapperClass;
		$wrapper->context = $this->context;
		return $wrapper;
	}


	/**
	 * Delegates the handling of file/directory operations to the underlying wrapper.
	 */
	public function __call(string $method, array $args)
	{
		$wrapper = $this->wrapper ?? $this->createUnderlyingWrapper();
		return method_exists($wrapper, $method)
			? $wrapper->$method(...$args)
			: false;
	}
}
