<?php

namespace Eznv\Tests;

use Eznv\Environment;
use Eznv\Project;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EnvironmentTest extends TestCase
{
    public function testFromDirectoryThrowsForInvalidPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        Environment::fromDirectory(__DIR__ . '/fixtures/does/not/exist');
    }

    public function testFromDirectoryThrowsWhenNotDirectory()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not a directory');

        Environment::fromDirectory(__DIR__ . '/fixtures/just-a-file');
    }

    public function testFromDirectoryThrowsWhenMissingComposerJson()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not been initialized');

        Environment::fromDirectory(__DIR__ . '/fixtures/uninitialized-environment');
    }

    public function testFromDirectoryThrowsForOldMetadata()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('older version of eznv');

        Environment::fromDirectory(__DIR__ . '/fixtures/old-style-metadata');
    }

    public function testFromDirectoryThrowsForMissingPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid project metadata');

        Environment::fromDirectory(__DIR__ . '/fixtures/missing-path');
    }

    public function testFromDirectory()
    {
        $path = __DIR__ . '/fixtures/initialized-environment';
        $environment = Environment::fromDirectory($path);

        $this->assertSame($path, $environment->path);
        $this->assertSame(__DIR__ . '/fixtures/project', $environment->project->path);
    }

    public function testFromProjectThrowsWhenDirectoryResolverNotSet()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('resolver not set');

        $project = new Project(__DIR__ . '/fixtures/project', 'eznv/project', 'library');

        // @todo test resolveEnvironmentDirectory() directly instead?
        Environment::fromProject($project);
    }

    public function testFromProject()
    {
        Environment::resolveEnvironmentDirectoryUsing(fn (Project $project) => __DIR__ . "/fixtures/{$project->hash}");

        $project = new Project(__DIR__ . '/fixtures/project', 'eznv/project', 'library');
        $environment = Environment::fromProject($project);

        $this->assertSame($project, $environment->project);

        Environment::resolveEnvironmentDirectoryUsing(null);
    }

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
        $wpPath = $path . '/public/wordpress';

        $environment = new Environment($path);

        $this->assertSame($wpPath, $environment->getWordPressPath());
        $this->assertSame($wpPath . '/one', $environment->getWordPressPath('one'));
        $this->assertSame($wpPath . '/path/to/db.sqlite', $environment->getWordPressPath('path/to/db.sqlite'));
    }

    public function testIsInitialized()
    {
        // Directory does not exist.
        $environment = new Environment(__DIR__ . '/fixtures/does/not/exist');

        $this->assertFalse($environment->isInitialized());

        // Directory does not contain composer.json
        $environment = new Environment(__DIR__ . '/fixtures/uninitialized-environment');

        $this->assertFalse($environment->isInitialized());

        // All good.
        $environment = new Environment(__DIR__ . '/fixtures/initialized-environment');

        $this->assertTrue($environment->isInitialized());
    }

    public function testIsOrphaned()
    {
        // Project not set
        $environment = new Environment(__DIR__ . '/fixtures/initialized-environment');

        $this->assertTrue($environment->isOrphaned());

        // Project directory does not exist.
        $project = new Project(__DIR__ . '/fixtures/does/not/exist', 'eznv/does-not-exist', 'library');
        $environment = new Environment(__DIR__ . '/fixtures/initialized-environment', $project);

        $this->assertTrue($environment->isOrphaned());

        // All good
        $project = new Project(__DIR__ . '/fixtures/project', 'eznv/project', 'library');
        $environment = new Environment(__DIR__ . '/fixtures/initialized-environment', $project);

        $this->assertFalse($environment->isOrphaned());
    }
}