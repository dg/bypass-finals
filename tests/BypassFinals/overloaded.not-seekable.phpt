<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable();


Assert::noError(function () {
	if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
		mime_content_type('/dev/null');
	}
});
