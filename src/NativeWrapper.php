<?php

declare(strict_types=1);

namespace DG\BypassFinals;


/**
 * Wrapper using native functions for working with files and directories.
 * @internal
 */
final class NativeWrapper
{
	public const Protocol = 'file';

	/** @var string */
	public $outerWrapper = MutatingWrapper::class;

	/** @var resource|null */
	public $context;

	/** @var resource|null */
	public $handle;


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


	public function dir_readdir()
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


	public function stream_cast(int $castAs)
	{
		return $this->handle;
	}


	public function stream_close(): void
	{
		fclose($this->handle);
	}


	public function stream_eof(): bool
	{
		return feof($this->handle);
	}


	public function stream_flush(): bool
	{
		return fflush($this->handle);
	}


	public function stream_lock(int $operation): bool
	{
		return $operation
			? flock($this->handle, $operation)
			: true;
	}


	public function stream_metadata(string $path, int $option, $value): bool
	{
		switch ($option) {
			case STREAM_META_TOUCH:
				return $this->native('touch', $path, $value[0] ?? time(), $value[1] ?? time());
			case STREAM_META_OWNER_NAME:
			case STREAM_META_OWNER:
				return $this->native('chown', $path, $value);
			case STREAM_META_GROUP_NAME:
			case STREAM_META_GROUP:
				return $this->native('chgrp', $path, $value);
			case STREAM_META_ACCESS:
				return $this->native('chmod', $path, $value);
			default:
				return false;
		}
	}


	public function stream_open(string $path, string $mode, int $options = 0, ?string &$openedPath = null): bool
	{
		$usePath = (bool) ($options & STREAM_USE_PATH);
		$this->handle = $this->context
			? $this->native('fopen', $path, $mode, $usePath, $this->context)
			: $this->native('fopen', $path, $mode, $usePath);
		return (bool) $this->handle;
	}


	public function stream_read(int $count)
	{
		return fread($this->handle, $count);
	}


	public function stream_seek(int $offset, int $whence = SEEK_SET): bool
	{
		return fseek($this->handle, $offset, $whence) === 0;
	}


	public function stream_set_option(int $option, int $arg1, ?int $arg2)
	{
		switch ($option) {
			case STREAM_OPTION_BLOCKING:
				return stream_set_blocking($this->handle, (bool) $arg1);
			case STREAM_OPTION_READ_BUFFER:
				return stream_set_read_buffer($this->handle, $arg2);
			case STREAM_OPTION_WRITE_BUFFER:
				return stream_set_write_buffer($this->handle, $arg2);
			case STREAM_OPTION_READ_TIMEOUT:
				return stream_set_timeout($this->handle, $arg1, $arg2);
			default:
				return false;
		}
	}


	public function stream_stat()
	{
		return fstat($this->handle);
	}


	public function stream_tell()
	{
		return ftell($this->handle);
	}


	public function stream_truncate(int $newSize): bool
	{
		return ftruncate($this->handle, $newSize);
	}


	public function stream_write(string $data)
	{
		return fwrite($this->handle, $data);
	}


	public function unlink(string $path): bool
	{
		return $this->native('unlink', $path);
	}


	public function url_stat(string $path, int $flags)
	{
		try {
			$func = $flags & STREAM_URL_STAT_LINK ? 'lstat' : 'stat';
			return $flags & STREAM_URL_STAT_QUIET
				? @$this->native($func, $path)
				: $this->native($func, $path);
		} catch (\RuntimeException $e) {
			// SplFileInfo::isFile throws exception
			return false;
		}
	}


	private function native(string $func)
	{
		stream_wrapper_restore(self::Protocol);
		try {
			return $func(...array_slice(func_get_args(), 1));
		} finally {
			stream_wrapper_unregister(self::Protocol);
			stream_wrapper_register(self::Protocol, $this->outerWrapper);
		}
	}
}
