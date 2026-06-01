<?php declare(strict_types=1);

namespace DG\BypassFinals;


/**
 * A stream wrapper class that uses native PHP functions for file and directory operations.
 * @internal
 */
final class NativeWrapper
{
	public const Protocol = 'file';

	/** Reference to the outer wrapper class for re-registration */
	public string $outerWrapper = MutatingWrapper::class;

	/** @var resource|null  Stream context, which may be set by stream functions */
	public $context;

	/** @var resource|null  File handle, which may be set by stream functions */
	public $handle;

	/** @var list<resource> */
	private static array $handles = [];

	private bool $isProcOpen = false;

	/** EOF is deferred until an empty read confirms it, matching native file:// handler semantics */
	private bool $eofAfterEmptyRead = false;


	public function dir_closedir(): void
	{
		closedir($this->handle);
	}


	public function dir_opendir(string $path, int $options): bool
	{
		$this->handle = $this->context
			? $this->native('opendir', $path, $this->context)
			: $this->native('opendir', $path);
		return (bool) $this->handle;
	}


	public function dir_readdir(): string|false
	{
		return readdir($this->handle);
	}


	public function dir_rewinddir(): bool
	{
		rewinddir($this->handle);
		return true;
	}


	public function mkdir(string $path, int $mode, int $options): bool
	{
		$recursive = (bool) ($options & STREAM_MKDIR_RECURSIVE);
		return $this->context
			? $this->native('mkdir', $path, $mode, $recursive, $this->context)
			: $this->native('mkdir', $path, $mode, $recursive);
	}


	public function rename(string $pathFrom, string $pathTo): bool
	{
		return $this->context
			? $this->native('rename', $pathFrom, $pathTo, $this->context)
			: $this->native('rename', $pathFrom, $pathTo);
	}


	public function rmdir(string $path, int $options): bool
	{
		return $this->context
			? $this->native('rmdir', $path, $this->context)
			: $this->native('rmdir', $path);
	}


	public function stream_cast(int $castAs): mixed
	{
		return $this->handle;
	}


	public function stream_close(): void
	{
		if ($this->isProcOpen) {
			self::$handles[] = $this->handle;
		} else {
			fclose($this->handle);
		}
	}


	public function stream_eof(): bool
	{
		return $this->eofAfterEmptyRead;
	}


	public function stream_flush(): bool
	{
		return fflush($this->handle);
	}


	public function stream_lock(int $operation): bool
	{
		return !$operation || flock($this->handle, $operation);
	}


	public function stream_metadata(string $path, int $option, mixed $value): bool
	{
		return match ($option) {
			STREAM_META_TOUCH => $this->native('touch', $path, $value[0] ?? time(), $value[1] ?? time()),
			STREAM_META_OWNER_NAME, STREAM_META_OWNER => $this->native('chown', $path, $value),
			STREAM_META_GROUP_NAME, STREAM_META_GROUP => $this->native('chgrp', $path, $value),
			STREAM_META_ACCESS => $this->native('chmod', $path, $value),
			default => false,
		};
	}


	public function stream_open(string $path, string $mode, int $options = 0, ?string &$openedPath = null): bool
	{
		$this->isProcOpen = debug_backtrace(0, 3)[2]['function'] === 'proc_open';
		$usePath = (bool) ($options & STREAM_USE_PATH);
		$this->handle = $this->context
			? $this->native('fopen', $path, $mode, $usePath, $this->context)
			: $this->native('fopen', $path, $mode, $usePath);
		return (bool) $this->handle;
	}


	public function stream_read(int $count): string|false
	{
		$data = fread($this->handle, $count);
		if ($data === '' || $data === false) {
			$this->eofAfterEmptyRead = true;
		}
		return $data;
	}


	public function stream_seek(int $offset, int $whence = SEEK_SET): bool
	{
		if (!stream_get_meta_data($this->handle)['seekable']) {
			return false;
		}
		$result = fseek($this->handle, $offset, $whence) === 0;
		if ($result) {
			$this->eofAfterEmptyRead = false;
		}
		return $result;
	}


	public function stream_set_option(int $option, int $arg1, ?int $arg2): int|bool
	{
		return match ($option) {
			STREAM_OPTION_BLOCKING => stream_set_blocking($this->handle, (bool) $arg1),
			STREAM_OPTION_READ_BUFFER => stream_set_read_buffer($this->handle, $arg2),
			STREAM_OPTION_WRITE_BUFFER => stream_set_write_buffer($this->handle, $arg2),
			STREAM_OPTION_READ_TIMEOUT => stream_set_timeout($this->handle, $arg1, $arg2),
			default => false,
		};
	}


	public function stream_stat(): array
	{
		return fstat($this->handle);
	}


	public function stream_tell(): int
	{
		return ftell($this->handle);
	}


	public function stream_truncate(int $newSize): bool
	{
		return ftruncate($this->handle, $newSize);
	}


	public function stream_write(string $data): int|false
	{
		return fwrite($this->handle, $data);
	}


	public function unlink(string $path): bool
	{
		return $this->native('unlink', $path);
	}


	public function url_stat(string $path, int $flags): array|false
	{
		if ($flags & STREAM_URL_STAT_QUIET) {
			set_error_handler(fn() => true);
		}
		try {
			$func = $flags & STREAM_URL_STAT_LINK ? 'lstat' : 'stat';
			return $this->native($func, $path);
		} catch (\RuntimeException) {
			// SplFileInfo::isFile throws exception
			return false;
		} finally {
			if ($flags & STREAM_URL_STAT_QUIET) {
				restore_error_handler();
			}
		}
	}


	/**
	 * Temporarily restores the native protocol handler to perform operations.
	 */
	private function native(string $func, mixed ...$args): mixed
	{
		stream_wrapper_restore(self::Protocol);
		try {
			return $func(...$args);
		} finally {
			stream_wrapper_unregister(self::Protocol);
			stream_wrapper_register(self::Protocol, $this->outerWrapper);
		}
	}
}
