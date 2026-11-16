<?php declare(strict_types=1);

// BypassFinals stays active on top of a wrapper that re-registers itself after each operation,
// as Infection's IncludeInterceptor does

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


final class SelfRegisteringWrapper
{
	/** @var resource|null */
	public $context;

	/** @var resource|null */
	private $handle;


	public static function enable(): void
	{
		stream_wrapper_unregister('file');
		stream_wrapper_register('file', self::class);
	}


	public static function disable(): void
	{
		stream_wrapper_restore('file');
	}


	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
	{
		self::disable();
		try {
			$this->handle = fopen($path, $mode) ?: null;
			return $this->handle !== null;
		} finally {
			self::enable();
		}
	}


	public function stream_read(int $count): string|false
	{
		return fread($this->handle, $count);
	}


	public function stream_eof(): bool
	{
		return feof($this->handle);
	}


	public function stream_seek(int $offset, int $whence): bool
	{
		return fseek($this->handle, $offset, $whence) === 0;
	}


	public function stream_write(string $data): int|false
	{
		return fwrite($this->handle, $data);
	}


	public function stream_flush(): bool
	{
		return fflush($this->handle);
	}


	public function stream_tell(): int|false
	{
		return ftell($this->handle);
	}


	public function stream_lock(int $operation): bool
	{
		return flock($this->handle, $operation);
	}


	public function stream_stat(): array|false
	{
		return fstat($this->handle);
	}


	public function stream_close(): void
	{
		fclose($this->handle);
	}


	public function stream_set_option(int $option, int $arg1, ?int $arg2): bool
	{
		return false;
	}


	public function dir_opendir(string $path, int $options): bool
	{
		self::disable();
		try {
			$this->handle = opendir($path) ?: null;
			return $this->handle !== null;
		} finally {
			self::enable();
		}
	}


	public function dir_readdir(): string|false
	{
		return readdir($this->handle);
	}


	public function dir_closedir(): bool
	{
		closedir($this->handle);
		return true;
	}


	public function mkdir(string $path, int $mode, int $options): bool
	{
		self::disable();
		try {
			return mkdir($path, $mode);
		} finally {
			self::enable();
		}
	}


	public function rmdir(string $path, int $options): bool
	{
		self::disable();
		try {
			return rmdir($path);
		} finally {
			self::enable();
		}
	}


	public function unlink(string $path): bool
	{
		self::disable();
		try {
			return unlink($path);
		} finally {
			self::enable();
		}
	}


	public function url_stat(string $path, int $flags): array|false
	{
		self::disable();
		try {
			return file_exists($path) || !($flags & STREAM_URL_STAT_QUIET) ? stat($path) : false;
		} finally {
			self::enable();
		}
	}
}


function currentWrapper(): string
{
	$handle = fopen(__FILE__, 'r');
	$wrapper = stream_get_meta_data($handle)['wrapper_data'] ?? null;
	fclose($handle);
	return $wrapper ? $wrapper::class : 'native';
}


SelfRegisteringWrapper::enable();
DG\BypassFinals::enable();
DG\BypassFinals::setCacheDirectory(getTempDir() . '/cache');
Assert::same(DG\BypassFinals\MutatingWrapper::class, currentWrapper());

// stream_open, with the cache
require __DIR__ . '/fixtures/final.class.php';
Assert::false((new ReflectionClass('FinalClass'))->isFinal());
Assert::same(DG\BypassFinals\MutatingWrapper::class, currentWrapper());

// url_stat
file_exists(__DIR__ . '/fixtures/final.excluded.class.php');
Assert::same(DG\BypassFinals\MutatingWrapper::class, currentWrapper());

require __DIR__ . '/fixtures/final.excluded.class.php';
Assert::false((new ReflectionClass('FinalClassExcluded'))->isFinal());

// dir_opendir, mkdir, rmdir, unlink
$dir = getTempDir() . '/dir';
mkdir($dir);
Assert::same(DG\BypassFinals\MutatingWrapper::class, currentWrapper());
Assert::contains('final.class.php', scandir(__DIR__ . '/fixtures'));
Assert::same(DG\BypassFinals\MutatingWrapper::class, currentWrapper());
rmdir($dir);
Assert::same(DG\BypassFinals\MutatingWrapper::class, currentWrapper());

// a PHP file without 'final' is served by the foreign wrapper, rewound by stream_seek(0, SEEK_SET)
$file = getTempDir() . '/plain.php';
file_put_contents($file, '<?php return "plain";');
Assert::same('plain', require $file);
unlink($file);
Assert::same(DG\BypassFinals\MutatingWrapper::class, currentWrapper());

// operations on an open handle do not undo a deliberate restore
$handle = fopen(__DIR__ . '/fixtures/final.property.php', 'r');
stream_wrapper_restore('file');
fread($handle, 10);
fstat($handle);
fclose($handle);
Assert::same('native', currentWrapper());
