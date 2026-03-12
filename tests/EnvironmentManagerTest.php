<?php

namespace Eznv\Tests;

use Eznv\EnvironmentManager;
use Eznv\Project;
use PHPUnit\Framework\TestCase;

class EnvironmentManagerTest extends TestCase
{
    public function testCreateForDirectory()
    {
        $environmentManager = new EnvironmentManager();

        $path = __DIR__ . '/fixtures/environment';
        $environment = $environmentManager->createForDirectory($path, validate: false);

        $this->assertSame($path, $environment->path);
        $this->assertNull($environment->projectPath);

        $path = __DIR__ . '/fixtures/initialized-environment';
        $environment = $environmentManager->createForDirectory($path, validate: false);

        $this->assertSame($path, $environment->path);
        $this->assertSame('/some/random/path', $environment->projectPath);
    }

    public function testCreateForProject()
    {
        $environmentManager = new EnvironmentManager();
        $project = new Project('/some/random/path', 'vendor/name', 'library');

        $environment = $environmentManager->createForProject($project, validate: false);

        // Environment path won't exist, therefore composer.json won't exists, so project path won't be set on environment...
        // Maybe we can revisit at some point with virtual file system in place?
        $this->assertStringEndsWith($project->hash, $environment->path);
    }

    public function testCreateForProjectId()
    {
        $environmentManager = new EnvironmentManager();
        $projectPath = '/some/random/path';
        $projectId = hash('sha256', $projectPath);

        $environment = $environmentManager->createForProjectHash($projectId, validate: false);

        // Environment path won't exist, therefore composer.json won't exists, so project path won't be set on environment...
        // Maybe we can revisit at some point with virtual file system in place?
        $this->assertStringEndsWith($projectId, $environment->path);
    }
}