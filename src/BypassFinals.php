<?php

namespace DG;


/**
 * Removes keyword final from source codes.
 */
class BypassFinals
{
	const PROTOCOL = 'file';

	/** @var resource|null */
	public $context;

	/** @var resource|null */
	private $handle;

	/** @var callable[] */
	private static $mutators = [__CLASS__, 'removeFinals'];


	public static function enable()
	{
		stream_wrapper_unregister(self::PROTOCOL);
		stream_wrapper_register(self::PROTOCOL, __CLASS__);
	}


	public function dir_closedir()
	{
		closedir($this->handle);
	}


	public function dir_opendir($path, $options)
	{
		$this->handle = $this->native('opendir', $path, $this->context);
		return (bool) $this->handle;
	}


	public function dir_readdir()
	{
		return readdir($this->handle);
	}


	public function dir_rewinddir()
	{
		return rewinddir($this->handle);
	}


	public function mkdir($path, $mode, $options)
	{
		return $this->native('mkdir', $mode, false, $this->context);
	}


	public function rename($pathFrom, $pathTo)
	{
		return $this->native('rename', $pathFrom, $pathTo, $this->context);
	}


	public function rmdir($path, $options)
	{
		return $this->native('rmdir', $this->context);
	}


	public function stream_cast($castAs)
	{
		return $this->handle;
	}


	public function stream_close()
	{
		fclose($this->handle);
	}


	public function stream_eof()
	{
		return feof($this->handle);
	}


	public function stream_flush()
	{
		return fflush($this->handle);
	}


	public function stream_lock($operation)
	{
		return flock($this->handle, $operation);
	}


	public function stream_metadata($path, $option, $value)
	{
		switch ($option) {
			case STREAM_META_TOUCH:
				return $this->native('touch', $path, $value[0], $value[1]);
			case STREAM_META_OWNER_NAME:
			case STREAM_META_OWNER:
				return $this->native('chown', $path, $value);
			case STREAM_META_GROUP_NAME:
			case STREAM_META_GROUP:
				return $this->native('chgrp', $path, $value);
			case STREAM_META_ACCESS:
				return $this->native('chmod', $path, $value);
		}
	}


	public function stream_open($path, $mode, $options, &$openedPath)
	{
		$usePath = (bool) ($options & STREAM_USE_PATH);
		if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
			$content = $this->native('file_get_contents', $path, $usePath, $this->context);
			if ($content === false) {
				return false;
			}
			$modified = self::removeFinals($content);
			if ($modified !== $content) {
				$this->handle = tmpfile();
				$this->native('fwrite', $this->handle, $modified);
				$this->native('fseek', $this->handle, 0);
				return true;
			}
		}
		$this->handle = $this->context
			? $this->native('fopen', $path, $mode, $usePath, $this->context)
			: $this->native('fopen', $path, $mode, $usePath);
		return (bool) $this->handle;
	}


	public function stream_read($count)
	{
		return fread($this->handle, $count);
	}


	public function stream_seek($offset, $whence = SEEK_SET)
	{
		return fseek($this->handle, $offset, $whence);
	}


	public function stream_set_option($option, $arg1, $arg2)
	{
	}


	public function stream_stat()
	{
		return fstat($this->handle);
	}


	public function stream_tell()
	{
		return ftell($this->handle);
	}


	public function stream_truncate($newSize)
	{
		return ftruncate($this->handle, $newSize);
	}


	public function stream_write($data)
	{
		return fwrite($this->handle, $data);
	}


	public function unlink($path)
	{
		return $this->native('unlink', $path);
	}


	public function url_stat($path, $flags)
	{
		return $this->native(
			$flags & STREAM_URL_STAT_LINK ? 'lstat' : 'stat',
			$path
		);
	}


	private function native($func)
	{
		stream_wrapper_restore(self::PROTOCOL);
		$res = call_user_func_array($func, array_slice(func_get_args(), 1));
		stream_wrapper_unregister(self::PROTOCOL);
		stream_wrapper_register(self::PROTOCOL, __CLASS__);
		return $res;
	}


	public static function removeFinals($code)
	{
		if (strpos($code, 'final') !== false) {
			$tokens = token_get_all($code);
			$code = '';
			foreach ($tokens as $token) {
				$code .= is_array($token)
					? ($token[0] === T_FINAL ? '' : $token[1])
					: $token;
			}
		}
		return $code;
	}
}
