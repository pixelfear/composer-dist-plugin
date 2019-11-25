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

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'download',
        ];
    }

    public function download(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();
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

        $url = strtr($config['url'], [
            '{$version}' => $version = $package->getPrettyVersion()
        ]);

        $subpackage = new Subpackage($package, $config['name'], $url);

        try {
            $downloadManager->download($subpackage, $path);
        } catch (TransportException $e) {
            // Allow failures when referencing a branch.
            // Users will likely be developing and be okay with manually compiling files.
            // We assume that tagged releases will exist and not 404.
            if (strpos($version, 'dev-') === 0) {
                $this->io->write('');
            } else {
                throw $e;
            }
        }
    }
}