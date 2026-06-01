<?php declare(strict_types=1);

// test that is_dir() inside stream_open() does not poison the stat cache (issue #67)

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();

DG\BypassFinals::enable(bypassReadOnly: false);

$filename = tempnam(sys_get_temp_dir(), 'bypass-finals-test');
$handle = fopen($filename, 'wb+');
fputcsv($handle, ['TestCol1', 'TestCol2', 'TestCol3']);
fclose($handle);

Assert::true(filesize($filename) > 0);

unlink($filename);
