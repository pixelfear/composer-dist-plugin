<?php

namespace Pixelfear\ComposerDistPlugin;

class Backup
{
    protected $files;
    protected $targetPath;
    protected $backupPath;

    public function __construct()
    {
        $this->setFilesystem(new Filesystem);
    }

    public function setFilesystem(Filesystem $files): void
    {
        $this->files = $files;
    }

    public function getFilesystem(): Filesystem
    {
        return $this->files;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    public function setTargetPath(string $path): void
    {
        $this->targetPath = $path;
    }

    public static function of(string $path): self
    {
        $backup = new static;

        $backup->setTargetPath($path);

        return $backup;
    }

    public function create(): self
    {
        if (! $this->files->exists($this->targetPath)) {
            return $this;
        }

        $this->backupPath = $this->targetPath . '-bak-' . substr(md5(uniqid('', true)), 0, 8);

        $this->files->copy($this->targetPath, $this->backupPath);

        return $this;
    }

    public function restore(): void
    {
        if ($this->backupPath) {
            $this->files->copy($this->backupPath, $this->targetPath);
        }
    }

    public function delete(): void
    {
        if ($this->backupPath) {
            $this->files->remove($this->backupPath);
        }
    }
}
