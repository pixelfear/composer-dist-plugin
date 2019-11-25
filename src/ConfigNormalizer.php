<?php

namespace Pixelfear\ComposerDistPlugin;

class ConfigNormalizer
{
    public function normalize(array $config)
    {
        if (isset($config['bundles'])) {
            $bundles = $config['bundles'];
        } else {
            $bundles = $config;
            $config = [];
        }

        if (array_keys($bundles)[0] !== 0) {
            $bundles = [$bundles];
        }

        foreach ($bundles as &$bundle) {
            $bundle['name'] = $bundle['name'] ?? 'dist';
        }

        return array_merge($config, ['bundles' => $bundles]);
    }
}
