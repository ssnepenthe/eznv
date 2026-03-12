<?php

namespace Eznv\Tests;

use Eznv\Project;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function testId()
    {
        $path = '/some/random/path';
        $name = 'vendor/name';
        $type = 'library';

        $project = new Project($path, $name, $type);

        $hash = hash('sha256', $path);

        $this->assertSame($hash, $project->hash);
        $this->assertSame(substr($hash, 0, 12), $project->id);
    }
}