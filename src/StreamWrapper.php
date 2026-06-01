<?php declare(strict_types=1);

namespace DG\BypassFinals;


/**
 * Prototype of the underlying stream wrapper used by MutatingWrapper for the actual file operations.
 * Mirrors PHP's documentation-only streamWrapper prototype: https://www.php.net/manual/en/class.streamwrapper.php
 * @property resource|null $context  Stream context, which may be set by stream functions
 * @internal
 */
interface StreamWrapper
{
	public function stream_open(string $path, string $mode, int $options = 0, ?string &$openedPath = null): bool;

	public function stream_read(int $count): string|false;

	public function stream_eof(): bool;

	public function stream_seek(int $offset, int $whence = SEEK_SET): bool;

	public function stream_close(): void;

	public function dir_opendir(string $path, int $options): bool;
}
