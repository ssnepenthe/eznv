<?php

namespace Eznv\Tests;

use Eznv\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testGetPath()
    {
        $path = '/some/random/path';

        $environment = new Environment($path);

        $this->assertSame($path . '/one', $environment->getPath('one'));
        $this->assertSame($path . '/one', $environment->getPath('/one/'));

        $this->assertSame($path . '/one/two', $environment->getPath('one', 'two'));
        $this->assertSame($path . '/one/two', $environment->getPath('/one/', '/two/'));

        $this->assertSame($path . '/one', $environment->getPath('one', ''));
    }

    public function testGetWordPressPath()
    {
        $path = '/some/random/path';
        $wpPath = $path . '/wordpress';

        $environment = new Environment($path);

        $this->assertSame($wpPath, $environment->getWordPressPath());
        $this->assertSame($wpPath . '/one', $environment->getWordPressPath('one'));
        $this->assertSame($wpPath . '/path/to/db.sqlite', $environment->getWordPressPath('path/to/db.sqlite'));
    }

    public function testGetInstalledPackageVersion()
    {
        // installed.php file won't be found at this path so we should always get null.
        $path = __DIR__ . '/fixtures';
        $environment = new Environment($path);

        $this->assertNull($environment->getInstalledPackageVersion('roots/wordpress'));
        $this->assertNull($environment->getInstalledPackageVersion('wpackagist-plugin/sqlite-database-integration'));

        $path = __DIR__ . '/fixtures/installed';
        $environment = new Environment($path);

        $this->assertSame('6.9.4', $environment->getInstalledPackageVersion('roots/wordpress'));
        $this->assertSame('2.2.18', $environment->getInstalledPackageVersion('wpackagist-plugin/sqlite-database-integration'));
    }
}