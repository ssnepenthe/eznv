<?php

namespace Eznv\Tests;

use Eznv\EnvironmentManager;
use Eznv\Project;
use PHPUnit\Framework\TestCase;

class EnvironmentManagerTest extends TestCase
{
    public function testCreate()
    {
        $environmentManager = new EnvironmentManager();

        $path = __DIR__ . '/fixtures/environment';
        $environment = $environmentManager->create($path);

        $this->assertSame($path, $environment->path);
        $this->assertNull($environment->project);

        $path = __DIR__ . '/fixtures/initialized-environment';
        $environment = $environmentManager->create($path);

        $this->assertSame($path, $environment->path);
        $this->assertSame(__DIR__ . '/fixtures/project', $environment->project->path);
    }

    public function testCreateForProject()
    {
        $environmentManager = new EnvironmentManager();
        $project = new Project('/some/random/path', 'vendor/name', 'library');

        $environment = $environmentManager->createForProject($project);

        // Environment path won't exist, therefore composer.json won't exists, so project path won't be set on environment...
        // Maybe we can revisit at some point with virtual file system in place?
        $this->assertStringEndsWith($project->hash, $environment->path);
    }
}