<?php

namespace Pixelfear\ComposerDistPlugin;

use Composer\Composer;
use Composer\Downloader\TransportException;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;
    protected $files;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
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

        $path = $installer->getInstallPath($package) . '/' . ($config['path'] ?? 'dist');

        // The downloader will delete the directory in order to unzip the file.
        // https://github.com/composer/composer/blob/88cff792cc6f2fa4df736e9abb612b955b783087/src/Composer/Downloader/FileDownloader.php#L123
        // If the download fails, the existing directory will be gone. We'll back it up and restore
        // when there's an error. If devs expect a failure and manually created or linked the
        // directory (eg. when working on a master branch), at least they won't lose it.
        $backup = Backup::of($path)->create();

        (new Filesystem)->remove($path);

        $url = strtr($config['url'], [
            '{$version}' => $version = $package->getPrettyVersion()
        ]);

        $subpackage = new Subpackage($package, $config['name'], $url);

        try {
            $this->attemptDownload($subpackage, $path);
        } catch (TransportException $e) {
            $backup->restore();

            // Allow failures when referencing a branch.
            // Users will likely be developing and be okay with manually compiling files.
            // We assume that tagged releases will exist and not 404.
            if (preg_match('/^dev-/', $version) || preg_match('/\.x-dev$/', $version)) {
                $this->writeDownloadFailedError($subpackage, $url);
            } else {
                throw $e;
            }
        } finally {
            $backup->delete();
        }
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        //
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        //
    }

    private function attemptDownload(Subpackage $package, string $path)
    {
        $downloadManager = $this->composer->getDownloadManager();

        if ($this->usingComposerTwo()) {
            $loop = $this->composer->getLoop();
            $loop->wait([$downloadManager->download($package, $path)]);
            $loop->wait([$downloadManager->install($package, $path)]);
        } else {
            $downloadManager->download($package, $path);
        }
    }

    private function writeDownloadFailedError(Subpackage $package, string $url)
    {
        if ($this->usingComposerTwo()) {
            $this->io->writeError('    <error>Failed to download</error>');
        } else {
            $this->io->write(''); // Composer v1 already shows "failed" in red.
        }
    }

    private function usingComposerTwo(): bool
    {
        return strpos(PluginInterface::PLUGIN_API_VERSION, '2') === 0;
    }
}
