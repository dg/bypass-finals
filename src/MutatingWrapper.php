<?php declare(strict_types=1);

namespace DG\BypassFinals;

use DG\BypassFinals;


/**
 * A stream wrapper class that mutates PHP source code by modifying 'final' and 'readonly' keywords.
 * @internal
 */
final class MutatingWrapper
{
	/** @var class-string<StreamWrapper>  Specifies the class of the underlying normal wrapper */
	public static string $underlyingWrapperClass;

	/** @var resource|null  Stream context, which may be set by stream functions */
	public $context;

	/**
	 * Instance of the actual underlying wrapper used for file operations.
	 * Native type is left as object so a pre-existing foreign 'file' wrapper (which only
	 * follows the protocol via duck-typing, without extending StreamWrapper) is accepted at runtime.
	 * @var StreamWrapper|null
	 */
	private ?object $wrapper = null;


	/**
	 * Opens a stream resource and creates $wrapper property. It can modify PHP source files if allowed by the path rules.
	 */
	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		if (is_dir($path)) {
			return false;
		}

		// is_dir() populates the stat cache; clear it so subsequent stat calls return fresh data
		clearstatcache(true, $path);

		$this->wrapper = $this->createUnderlyingWrapper();
		if (!$this->wrapper->stream_open($path, $mode, $options, $openedPath)) {
			return false;
		}

		if ($mode === 'rb' && pathinfo($path, PATHINFO_EXTENSION) === 'php' && BypassFinals::isPathAllowed($path)) {
			$content = '';
			while (!$this->wrapper->stream_eof()) {
				$content .= $this->wrapper->stream_read(8192);
			}

			$modified = BypassFinals::modifyCode($content, $path);
			if ($modified === $content) {
				$this->wrapper->stream_seek(0);
			} else {
				$this->wrapper->stream_close();
				$wrapper = new NativeWrapper;
				$wrapper->handle = tmpfile() ?: throw new \RuntimeException('Unable to create a temporary file.');
				$wrapper->stream_write($modified);
				$wrapper->stream_seek(0);
				$this->wrapper = $wrapper;
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
	 * Native return type is object (not StreamWrapper) so a pre-existing foreign 'file' wrapper is accepted at runtime.
	 * @return StreamWrapper
	 */
	private function createUnderlyingWrapper(): object
	{
		$wrapper = new self::$underlyingWrapperClass;
		$wrapper->context = $this->context;
		return $wrapper;
	}


	/**
	 * Delegates the handling of file/directory operations to the underlying wrapper.
	 * @param  mixed[]  $args
	 */
	public function __call(string $method, array $args): mixed
	{
		$wrapper = $this->wrapper ?? $this->createUnderlyingWrapper();
		return method_exists($wrapper, $method)
			? $wrapper->$method(...$args)
			: false;
	}
}
