<?php

namespace Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use Pixelfear\ComposerDistPlugin\Backup;
use Pixelfear\ComposerDistPlugin\Filesystem;

class BackupTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    /** @test */
    function it_creates_a_backup_object_from_a_directory()
    {
        $backup = Backup::of('path/to/dist');

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertInstanceOf(Filesystem::class, $backup->getFilesystem());
        $this->assertEquals('path/to/dist', $backup->getTargetPath());
    }

    /** @test */
    function it_backs_up_a_directory()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->with('path/to/dist')->once()->andReturnTrue();
        $files->shouldReceive('copy')->withArgs(function ($from, $to) {
            return $from === 'path/to/dist'
                && strpos($to, 'path/to/dist-bak-') === 0;
        })->once();

        $backup = Backup::of('path/to/dist');
        $backup->setFilesystem($files);

        $this->assertSame($backup, $backup->create());
    }

    /** @test */
    function it_doesnt_backup_a_directory_if_it_doesnt_exist()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->with('path/to/dist')->once()->andReturnFalse();
        $files->shouldNotReceive('copy');

        $backup = Backup::of('path/to/dist');
        $backup->setFilesystem($files);

        $this->assertSame($backup, $backup->create());
    }

    /** @test */
    function it_restores_a_backup()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->with('path/to/dist')->once()->andReturnTrue();
        $files->shouldReceive('copy')->withArgs(function ($from, $to) {
            return $from === 'path/to/dist'
                && strpos($to, 'path/to/dist-bak-') === 0;
        })->once()->ordered();
        $files->shouldReceive('copy')->withArgs(function ($from, $to) {
            return strpos($from, 'path/to/dist-bak-') === 0
                && $to === 'path/to/dist';
        })->once()->ordered();

        $backup = Backup::of('path/to/dist');
        $backup->setFilesystem($files);
        $backup->create();

        $this->assertNull($backup->restore());
    }

    /** @test */
    function it_doesnt_restore_if_the_original_directory_never_existed()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->with('path/to/dist')->once()->andReturnFalse();
        $files->shouldNotReceive('copy');

        $backup = Backup::of('path/to/dist');
        $backup->setFilesystem($files);
        $backup->create();

        $this->assertNull($backup->restore());
    }

    /** @test */
    function it_deletes_a_backup()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->with('path/to/dist')->once()->andReturnTrue();
        $files->shouldReceive('copy')->withArgs(function ($from, $to) {
            return $from === 'path/to/dist'
                && strpos($to, 'path/to/dist-bak-') === 0;
        })->once()->ordered();
        $files->shouldReceive('remove')->withArgs(function ($path) {
            return strpos($path, 'path/to/dist-bak-') === 0;
        })->once()->ordered();

        $backup = Backup::of('path/to/dist');
        $backup->setFilesystem($files);
        $backup->create();

        $this->assertNull($backup->delete());
    }

    /** @test */
    function it_doesnt_delete_if_the_original_directory_never_existed()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('exists')->with('path/to/dist')->once()->andReturnFalse();
        $files->shouldNotReceive('remove');

        $backup = Backup::of('path/to/dist');
        $backup->setFilesystem($files);
        $backup->create();

        $this->assertNull($backup->delete());
    }
}
