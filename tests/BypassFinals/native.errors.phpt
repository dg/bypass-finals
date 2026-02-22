<?php declare(strict_types=1);

// test native php functions - for comparison with overloaded.errors.phpt

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


Assert::error(function () {
	chmod('unknown', 0777);
}, E_WARNING);

Assert::error(function () {
	copy('unknown', 'unknown2');
}, E_WARNING);

Assert::false(file_exists('unknown'));

Assert::error(function () {
	file_get_contents('unknown');
}, E_WARNING);

if (PHP_VERSION_ID >= 70400) {
	Assert::error(function () {
		file_get_contents(__DIR__);
	}, defined('PHP_WINDOWS_VERSION_BUILD') ? E_WARNING : E_NOTICE);
}

Assert::error(function () {
	file_put_contents(__DIR__, 'content');
}, E_WARNING);

Assert::error(function () {
	file('unknown');
}, E_WARNING);

Assert::error(function () {
	fileatime('unknown');
}, E_WARNING);

Assert::error(function () {
	filectime('unknown');
}, E_WARNING);

Assert::error(function () {
	filegroup('unknown');
}, E_WARNING);

Assert::error(function () {
	fileinode('unknown');
}, E_WARNING);

Assert::error(function () {
	filemtime('unknown');
}, E_WARNING);

Assert::error(function () {
	fileowner('unknown');
}, E_WARNING);

Assert::error(function () {
	fileperms('unknown');
}, E_WARNING);

Assert::error(function () {
	filesize('unknown');
}, E_WARNING);

Assert::error(function () {
	filetype('unknown');
}, E_WARNING);

Assert::error(function () {
	fopen('unknown', 'r');
}, E_WARNING);

Assert::same([], glob('unknown'));
Assert::false(is_dir('unknown'));
Assert::false(is_executable('unknown'));
Assert::false(is_file('unknown'));
Assert::false((new SplFileInfo('unknown'))->isFile());
Assert::false(is_link('unknown'));
Assert::false(is_readable('unknown'));
Assert::false(is_writable('unknown'));

if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
	Assert::error(function () {
		chgrp('unknown', 'group');
	}, E_WARNING);

	Assert::error(function () {
		chown('unknown', 'user');
	}, E_WARNING);

	Assert::error(function () {
		lchgrp('unknown', 'group');
	}, E_WARNING);

	Assert::error(function () {
		lchown('unknown', 'user');
	}, E_WARNING);
}

Assert::error(function () {
	link('unknown', 'unknown2');
}, E_WARNING);

Assert::error(function () {
	linkinfo('unknown');
}, E_WARNING);

Assert::error(function () {
	lstat('unknown');
}, E_WARNING);

Assert::error(function () {
	mkdir(__DIR__);
}, E_WARNING);

Assert::error(function () {
	parse_ini_file('unknown');
}, E_WARNING);

Assert::error(function () {
	readfile('unknown');
}, E_WARNING);

Assert::error(function () {
	readlink('unknown');
}, E_WARNING);

Assert::false(realpath('unknown'));

Assert::error(function () {
	rename('unknown', 'unknown2');
}, E_WARNING);

Assert::error(function () {
	rmdir('unknown');
}, E_WARNING);

Assert::error(function () {
	stat('unknown');
}, E_WARNING);

Assert::error(function () {
	unlink('unknown');
}, E_WARNING);

Assert::same(-1, fseek(fopen(__FILE__, 'r'), -1));

// not tested: symlink(), touch()
