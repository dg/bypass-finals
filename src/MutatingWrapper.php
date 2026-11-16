<?php declare(strict_types=1);

namespace DG\BypassFinals;

use DG\BypassFinals;
use function in_array;


/**
 * A stream wrapper class that mutates PHP source code by modifying 'final' and 'readonly' keywords.
 * @internal
 */
final class MutatingWrapper
{
	/** @var class-string  Specifies the class of the underlying normal wrapper */
	public static string $underlyingWrapperClass;

	/** @var resource|null  Stream context, which may be set by stream functions */
	public $context;

	/** Instance of the actual underlying wrapper used for file operations */
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
		try {
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
					$this->wrapper->stream_seek(0, SEEK_SET);
				} else {
					$this->wrapper->stream_close();
					$wrapper = new NativeWrapper;
					$wrapper->handle = tmpfile() ?: throw new \RuntimeException('Unable to create a temporary file.');
					$wrapper->stream_write($modified);
					$wrapper->stream_seek(0, SEEK_SET);
					$this->wrapper = $wrapper;
				}
			}

			return true;

		} finally {
			$this->keepOnTop();
		}
	}


	/**
	 * Delegates the handling of directory opening to the underlying wrapper and creates $wrapper property.
	 */
	public function dir_opendir(string $path, int $options): bool
	{
		$this->wrapper = $this->createUnderlyingWrapper();
		try {
			return $this->wrapper->dir_opendir($path, $options);
		} finally {
			$this->keepOnTop();
		}
	}


	/**
	 * Instantiates the underlying wrapper.
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
		try {
			return method_exists($wrapper, $method)
				? $wrapper->$method(...$args)
				: false;
		} finally {
			if (in_array($method, ['url_stat', 'unlink', 'rename', 'mkdir', 'rmdir', 'stream_metadata'], true)) {
				$this->keepOnTop();
			}
		}
	}


	/**
	 * A foreign underlying wrapper may re-register itself for the protocol after a path operation
	 * (e.g. Infection's IncludeInterceptor), which would switch BypassFinals off. PHP dispatched that
	 * operation to this wrapper, so it was on top, and this puts it back. Operations on an open handle
	 * do not, so a later deliberate stream_wrapper_restore() is not undone by reading an old handle.
	 */
	private function keepOnTop(): void
	{
		if (self::$underlyingWrapperClass !== NativeWrapper::class) {
			if (in_array(NativeWrapper::Protocol, stream_get_wrappers(), true)) {
				stream_wrapper_unregister(NativeWrapper::Protocol);
			}
			stream_wrapper_register(NativeWrapper::Protocol, self::class);
		}
	}
}
