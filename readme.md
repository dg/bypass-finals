Bypass Finals
=============

[![Downloads this Month](https://img.shields.io/packagist/dm/dg/bypass-finals.svg)](https://packagist.org/packages/dg/bypass-finals)
[![Tests](https://github.com/dg/bypass-finals/workflows/Tests/badge.svg?branch=master)](https://github.com/dg/bypass-finals/actions)
[![Latest Stable Version](https://poser.pugx.org/dg/bypass-finals/v/stable)](https://github.com/dg/bypass-finals/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/dg/bypass-finals/blob/master/license.md)


Introduction
------------

Removes `final` and `readonly` keywords from source code on-the-fly and allows mocking of final methods and classes.
It can be used together with any test tool such as PHPUnit, Mockery or [Nette Tester](https://tester.nette.org).


Installation
------------

The recommended way to install is through Composer:

```
composer require dg/bypass-finals --dev
```

It requires PHP version 7.1 and supports PHP up to 8.3.


Usage
-----

Simply call this:

```php
DG\BypassFinals::enable();
```

You need to enable it before the classes you want to remove the keywords from are loaded. So call it as soon as possible,
preferably right after `vendor/autoload.php` is loaded.

Note that final internal PHP classes like `Closure` cannot be mocked.

The removal of `readonly` keywords can be disabled using the parameter:

```php
DG\BypassFinals::enable(bypassReadOnly: false);
```

You can choose to only bypass keywords in specific files or directories:

```php
DG\BypassFinals::setWhitelist([
    '*/Nette/*',
]);
```

This gives you finer control and can solve issues with certain frameworks and libraries.

You can try to increase performance by using the cache (the directory must exist):

```php
DG\BypassFinals::setCacheDirectory(__DIR__ . '/cache');
```

To register BypassFinals in PHPUnit 10, simply add the extension to the PHPUnit XML configuration file:

```xml
<extensions>
	<bootstrap class="DG\BypassFinals\PHPUnitExtension"/>
</extensions>
```

Support Project
---------------

Do you like BypassFinals?

[![Donate](https://files.nette.org/icons/donation-1.svg?)](https://nette.org/make-donation?to=bypass-finals)
