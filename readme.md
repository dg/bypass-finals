[![bypass-finals](https://github.com/dg/bypass-finals/assets/194960/b299faba-77ee-41ac-8cb7-a482318dcacd)](https://phpfashion.com/en/how-to-mock-final-classes)

[![Downloads this Month](https://img.shields.io/packagist/dm/dg/bypass-finals.svg)](https://packagist.org/packages/dg/bypass-finals)
[![Tests](https://github.com/dg/bypass-finals/workflows/Tests/badge.svg?branch=master)](https://github.com/dg/bypass-finals/actions)
[![Latest Stable Version](https://poser.pugx.org/dg/bypass-finals/v/stable)](https://github.com/dg/bypass-finals/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/dg/bypass-finals/blob/master/license.md)

 <!---->

Introduction
------------

**BypassFinals** effortlessly strips away `final` and `readonly` keywords from your PHP code on-the-fly.
This handy tool makes it possible to mock final methods and classes, seamlessly integrating with popular
testing frameworks like PHPUnit, Mockery, or [Nette Tester](https://tester.nette.org).

 <!---->

Installation
------------

The easiest way to install BypassFinals is via Composer. Just run the following command in your project directory:

```
composer require dg/bypass-finals --dev
```

It pretty much runs everywhere: PHP 7.1 through 8.4 are all supported!

 <!---->

Usage
-----

To get BypassFinals up and running, just invoke:

```php
DG\BypassFinals::enable();
```

Make sure to call this method early, preferably immediately after your `vendor/autoload.php` is loaded,
to ensure all classes are processed before they are used.

Note that final internal PHP classes like `Closure` are not mockable.

To avoid removing `readonly` keywords, you can disable this feature by passing a parameter:

```php
DG\BypassFinals::enable(bypassReadOnly: false);
```

To narrow down the application scope of BypassFinals, use a whitelist to specify directories or files:

```php
DG\BypassFinals::allowPaths([
	'*/Nette/*',
]);
```

Or, conversely, you can specify which paths not to search using `DG\BypassFinals::denyPaths()`.
This gives you finer control and can solve issues with certain frameworks and libraries.

Enhance performance by caching transformed files. Make sure the cache directory already exists:

```php
DG\BypassFinals::setCacheDirectory(__DIR__ . '/cache');
```

For integration with PHPUnit 10 or newer, simply add BypassFinals as an extension in your PHPUnit XML configuration file:

```xml
<extensions>
	<bootstrap class="DG\BypassFinals\PHPUnitExtension"/>
</extensions>
```

Optionally you can configure BypassFinals in your `phpunit.xml` file:

```xml
<extensions>
	<bootstrap class="DG\BypassFinals\PHPUnitExtension">
		<parameter name="bypassFinal" value="true"/>
		<parameter name="bypassReadOnly" value="false"/>
		<parameter name="cacheDirectory" value="./cache"/>
	</bootstrap>
</extensions>
```

 <!---->

Troubleshooting
---------------

If you encounter issues with BypassFinals not working as expected, you can use the `debugInfo()` method to gain insights into its internal state. Calling this method will output valuable information to help diagnose the problem:

```php
DG\BypassFinals::debugInfo();
```

This will display:

- Configuration: Whether BypassFinals is enabled for removing `final` and/or `readonly` keywords.
- BypassFinals startup call stack: The sequence of function calls that led to `DG\BypassFinals::enable()`, helping you verify when and where BypassFinals was started.
- Classes loaded before BypassFinals startup: A list of classes that were already loaded in PHP before BypassFinals was started. Keywords in these classes cannot be removed, as the classes are already defined. This can help identify potential conflicts or reasons why certain classes aren't being modified.
- Modified files: A list of the files that BypassFinals has successfully modified. If the file containing a class you expect to be modified isn't in this list, it suggests a problem with path matching or the timing of the BypassFinals startup.

By examining this output, you can better understand how BypassFinals is configured and whether it's operating on the intended files and classes. This can significantly speed up the process of identifying and resolving issues.

 <!---->

Do you like this project?
---------

Check out my other innovative open-source projects that might catch your interest:

<h3>

✅ [Latte](https://latte.nette.org): The only safe and intuitive templating system for PHP<br>
✅ [Tracy](https://tracy.nette.org): An addictive debugging tool to enhance your development workflow<br>
✅ [PhpGenerator](https://doc.nette.org/en/php-generator): A robust library for generating PHP code with modern features<br>
✅ [Nette Framework](https://nette.org): A thoughtfully engineered and popular web framework.<br>

</h3>

 <!---->

Support Project
---------------

[![Donate](https://files.nette.org/icons/donation-1.svg?)](https://nette.org/make-donation?to=bypass-finals)
