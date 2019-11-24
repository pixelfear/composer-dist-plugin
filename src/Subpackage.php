<?php

namespace Pixelfear\ComposerDistPlugin;

use Composer\Package\Package;
use Composer\Package\PackageInterface;
use RuntimeException;

class Subpackage extends Package
{
    private $parent;

    public function __construct(PackageInterface $parent, $name, $url)
    {
        parent::__construct(
            $parent->getName(),
            $parent->getVersion(),
            $name
        );

        $this->setDistUrl($url);
        $this->setDistType($this->parseDistType($url));
        $this->setInstallationSource('dist');
    }

    protected function parseDistType($url)
    {
        $parts = parse_url($url);
        $filename = pathinfo($parts['path'], PATHINFO_BASENAME);

        if (preg_match('/\.zip$/', $filename)) {
            return 'zip';
        }

        if (preg_match('/\.(tar\.gz|tgz)$/', $filename)) {
            return 'tar';
        }

        throw new RuntimeException("Failed to determine archive type for $filename");
    }
}
