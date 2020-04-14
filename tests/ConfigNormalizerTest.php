<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pixelfear\ComposerDistPlugin\ConfigNormalizer;

class ConfigNormalizerTest extends TestCase
{
    /** @test */
    function it_allows_an_object_for_a_single_bundle()
    {
        $config = [
            'name' => 'test',
            'url' => '/url',
        ];

        $expected = [
            'bundles' => [
                [
                    'name' => 'test',
                    'url' => '/url'
                ]
            ]
        ];

        $this->assertEquals($expected, (new ConfigNormalizer)->normalize($config));
    }

    /** @test */
    function it_adds_a_default_name_if_omitted()
    {
        $config = [
            'url' => '/url',
        ];

        $expected = [
            'bundles' => [
                [
                    'name' => 'dist',
                    'url' => '/url'
                ]
            ]
        ];

        $this->assertEquals($expected, (new ConfigNormalizer)->normalize($config));
    }

    /** @test */
    function it_allows_an_an_array_of_objects_for_multiple_bundles()
    {
        $config = [
            [
                'url' => '/url',
            ],
            [
                'name' => 'second',
                'url' => '/second-url',
            ],
        ];

        $expected = [
            'bundles' => [
                [
                    'name' => 'dist',
                    'url' => '/url',
                ],
                [
                    'name' => 'second',
                    'url' => '/second-url',
                ],
            ]
        ];

        $this->assertEquals($expected, (new ConfigNormalizer)->normalize($config));
    }

    /** @test */
    function it_allows_bundles_to_be_nested_in_its_own_key_for_extra_configuration()
    {
        $config = [
            'foo' => 'bar',
            'bundles' => [
                [
                    'url' => '/url',
                ],
                [
                    'name' => 'second',
                    'url' => '/second-url',
                ],
            ]
        ];

        $expected = [
            'foo' => 'bar',
            'bundles' => [
                [
                    'name' => 'dist',
                    'url' => '/url',
                ],
                [
                    'name' => 'second',
                    'url' => '/second-url',
                ],
            ]
        ];

        $this->assertEquals($expected, (new ConfigNormalizer)->normalize($config));
    }
}
