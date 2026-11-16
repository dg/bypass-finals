<?php declare(strict_types=1);

// test debugInfo()

use DG\BypassFinals;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function debugInfo(): string
{
	ob_start();
	BypassFinals::debugInfo();
	return ob_get_clean();
}


BypassFinals::enable(bypassReadOnly: false);
BypassFinals::allowPaths(['*/fixtures/*']);
BypassFinals::denyPaths(['*/fixtures/denied/*', '!*/fixtures/denied/excepted/*']);

Assert::match(<<<'XX'
	<xmp>
	BypassFinals Debug Information
	------------------------------

	Configuration:
	  Bypass 'final': enabled
	  Bypass 'readonly': disabled
	  Allowed paths: */fixtures/*
	  Denied paths: */fixtures/denied/*, !*/fixtures/denied/excepted/*
	  (in both lists the last matching mask wins, '!' marks an exception)
	  Cache directory: not set

	From where BypassFinals::enable() was started:
	  #0 DG\BypassFinals::enable() in %a%BypassFinals.debugInfo.phpt:%d%

	Classes already loaded before BypassFinals was started:
	%A%  - Tester\Assert
	%A%
	Files where BypassFinals removed final/readonly:
	  no files were modified
	</xmp>
	XX, debugInfo());

require __DIR__ . '/fixtures/final.class.php';

Assert::match(<<<'XX'
	%A%Files where BypassFinals removed final/readonly:
	  - %a%final.class.php
	</xmp>
	XX, debugInfo());
