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
}