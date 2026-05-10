<?php declare(strict_types=1);

// Regression test for issue #58:
// bootstrap.php can be loaded BEFORE vendor/autoload.php so that classes
// registered in autoload.files have their final keyword stripped.

use Tester\Assert;

require __DIR__ . '/../../vendor/autoload.php';

Tester\Environment::setup();


$bootstrap = realpath(__DIR__ . '/../../src/bootstrap.php');
$autoload = realpath(__DIR__ . '/../../vendor/autoload.php');
$fixture = realpath(__DIR__ . '/fixtures/final.class.php');

$script = tempnam(sys_get_temp_dir(), 'bypass_');
file_put_contents($script, <<<PHP
<?php
require '$bootstrap';
require '$autoload';
require '$fixture';
class EarlyLoadTest extends FinalClass {}
echo 'success';
PHP);

$output = shell_exec('php ' . escapeshellarg($script));
@unlink($script);

Assert::same('success', trim($output));
