<?php

namespace Pixelfear\ComposerDistPlugin;

use Composer\Util\Filesystem as ComposerFilesystem;

class Filesystem
{
    private $composer;

    public function __construct()
    {
        $this->composer = new ComposerFilesystem;
    }

    public function exists(string $path)
    {
        return file_exists($path);
    }

    public function copy(string $from, string $to)
    {
        $this->composer->copy($from, $to);
    }

    public function remove(string $path)
    {
        $this->composer->remove($path);
    }
}
