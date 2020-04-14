<?php

namespace Pixelfear\ComposerDistPlugin;

use Composer\Composer;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\Filesystem;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;
    protected $files;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->files = new Filesystem;
    }

    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'downloadInstall',
            PackageEvents::POST_PACKAGE_UPDATE => 'downloadUpdate',
        ];
    }

    public function downloadInstall(PackageEvent $event)
    {
        $this->download($event->getOperation()->getPackage());
    }

    public function downloadUpdate(PackageEvent $event)
    {
        $this->download($event->getOperation()->getTargetPackage());
    }

    protected function download($package)
    {
        $extra = $package->getExtra();

        if (! isset($extra['download-dist'])) {
            return;
        }

        $config = (new ConfigNormalizer)->normalize($extra['download-dist']);

        foreach ($config['bundles'] as $bundle) {
            $this->downloadBundle($package, $bundle);
        }
    }

    protected function downloadBundle($package, $config)
    {
        $installer = $this->composer->getInstallationManager();
        $downloadManager = $this->composer->getDownloadManager();

        $path = $installer->getInstallPath($package) . '/' . ($config['path'] ?? 'dist');

        // The downloader will delete the directory in order to unzip the file.
        // https://github.com/composer/composer/blob/88cff792cc6f2fa4df736e9abb612b955b783087/src/Composer/Downloader/FileDownloader.php#L123
        // If the download fails, the existing directory will be gone. We'll back it up and restore
        // when there's an error. If devs expect a failure and manually created or linked the
        // directory (eg. when working on a master branch), at least they won't lose it.
        $backupPath = $this->backupDirectory($path);

        $url = strtr($config['url'], [
            '{$version}' => $version = $package->getPrettyVersion()
        ]);

        $subpackage = new Subpackage($package, $config['name'], $url);

        try {
            $downloadManager->download($subpackage, $path);
        } catch (TransportException $e) {
            $this->restoreBackup($backupPath, $path);

            // Allow failures when referencing a branch.
            // Users will likely be developing and be okay with manually compiling files.
            // We assume that tagged releases will exist and not 404.
            if (strpos($version, 'dev-') === 0) {
                $this->io->write('');
            } else {
                throw $e;
            }
        } finally {
            $this->deleteBackup($backupPath);
        }
    }

    protected function backupDirectory($path)
    {
        if (! file_exists($path)) {
            return false;
        }

        $this->files->copy($path, $backupPath = $path . '-bak-' . substr(md5(uniqid('', true)), 0, 8));

        return $backupPath;
    }

    protected function restoreBackup($backupPath, $path)
    {
        if ($backupPath) {
            $this->files->copy($backupPath, $path);
        }
    }

    protected function deleteBackup($backupPath)
    {
        if ($backupPath) {
            $this->files->remove($backupPath);
        }
    }
}
