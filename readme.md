Bypass Finals
=============

[![Downloads this Month](https://img.shields.io/packagist/dm/dg/bypass-finals.svg)](https://packagist.org/packages/dg/bypass-finals)
[![Build Status](https://travis-ci.org/dg/bypass-finals.svg?branch=master)](https://travis-ci.org/dg/bypass-finals)
[![Latest Stable Version](https://poser.pugx.org/dg/bypass-finals/v/stable)](https://github.com/dg/bypass-finals/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/dg/bypass-finals/blob/master/license.md)


Introduction
------------

Removes final keywords from source code on-the-fly and allows mocking of final methods and classes.
It can be used together with any test tool such as PHPUnit, Mockery or [Nette Tester](https://tester.nette.org).


Installation
------------

The recommended way to install is through Composer:

```
composer require dg/bypass-finals --dev
```

It requires PHP version 7.1 (or 5.6 in case of release 1.1) and supports PHP up to 8.0.


Usage
-----

Simply call this:

```php
DG\BypassFinals::enable();
```

You need to enable it before the classes you want to remove the final are loaded. So call it as soon as possible,
preferably right after `vendor/autoload.php` is loaded.

Note that final internal PHP classes like `Closure` cannot be mocked.

You can choose to only bypass finals in specific files or directories:

```php
DG\BypassFinals::setWhitelist([
    '*/Nette/*',
]);
```

This gives you finer control and can solve issues with certain frameworks and libraries.


Support Project
---------------

Do you like BypassFinals?

[![Donate](https://files.nette.org/icons/donation-1.svg?)](https://nette.org/make-donation?to=bypass-finals)
