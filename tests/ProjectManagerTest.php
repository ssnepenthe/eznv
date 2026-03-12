<?php

namespace Eznv\Tests;

use Eznv\Environment;
use Eznv\ProjectManager;
use PHPUnit\Framework\TestCase;

class ProjectManagerTest extends TestCase
{
    public function testCreateForCwd()
    {
        $path = __DIR__ . '/fixtures/project';
        $origCwd = getcwd();

        chdir($path);

        $projectManager = new ProjectManager();
        $project = $projectManager->createForCwd();

        chdir($origCwd);

        $this->assertSame($path, $project->path);
        $this->assertSame('eznv/project', $project->name);
        // No type set - defaults to "library"
        $this->assertSame('library', $project->type);
    }

    public function testCreateForDirectory()
    {
        $projectManager = new ProjectManager();

        $path = __DIR__ . '/fixtures/project';
        $project = $projectManager->createForDirectory($path);

        $this->assertSame($path, $project->path);
        $this->assertSame('eznv/project', $project->name);
        $this->assertSame('library', $project->type);

        $path = __DIR__ . '/fixtures/typed-project';
        $project = $projectManager->createForDirectory($path);

        $this->assertSame($path, $project->path);
        $this->assertSame('eznv/typed-project', $project->name);
        $this->assertSame('wordpress-plugin', $project->type);
    }

    public function testCreateForEnvironment()
    {
        $projectManager = new ProjectManager();
        $path = __DIR__ . '/fixtures/project';
        $environment = new Environment('/some/irrelevant/path', $path);

        $project = $projectManager->createForEnvironment($environment);

        $this->assertSame($path, $project->path);
        $this->assertSame('eznv/project', $project->name);
        $this->assertSame('library', $project->type);
    }
}