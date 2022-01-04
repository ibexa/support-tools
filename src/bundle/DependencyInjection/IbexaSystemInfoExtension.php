<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\SystemInfo\DependencyInjection;

use Ibexa\Bundle\SystemInfo\SystemInfo\Collector\IbexaSystemInfoCollector;
use Ibexa\Bundle\SystemInfo\SystemInfo\Value\IbexaSystemInfo;
use Ibexa\Contracts\Core\Ibexa;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class IbexaSystemInfoExtension extends Extension implements PrependExtensionInterface
{
    public const EXTENSION_NAME = 'ibexa_system_info';
    public const METRICS_TAG = 'ibexa.system_info.metrics';
    public const SERVICE_TAG = 'ibexa.system_info.service';

    public function getAlias()
    {
        return self::EXTENSION_NAME;
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
        $loader->load('default_settings.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (isset($config['system_info']) && $config['system_info']['powered_by']['enabled']) {
            $container->setParameter(
                'ezplatform_support_tools.system_info.powered_by.name',
                $this->getPoweredByName(
                    $container,
                    $config['system_info']['powered_by']['release']
                )
            );
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        $this->prependJMSTranslation($container);
    }

    private function getPoweredByName(ContainerBuilder $container, ?string $release): string
    {
        $vendor = $container->getParameter('kernel.project_dir') . '/vendor/';

        // Autodetect product name
        $name = self::getNameByPackages($vendor);

        if ($release === 'major') {
            $name .= ' v' . (int)Ibexa::VERSION;
        } elseif ($release === 'minor') {
            $version = explode('.', Ibexa::VERSION);
            $name .= ' v' . $version[0] . '.' . $version[1];
        }

        return $name;
    }

    private function prependJMSTranslation(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('jms_translation', [
            'configs' => [
                'ibexa_support_tools' => [
                    'dirs' => [
                        __DIR__ . '/../../../src/',
                    ],
                    'output_dir' => __DIR__ . '/../Resources/translations/',
                    'output_format' => 'xliff',
                    'excluded_dirs' => ['Behat', 'Tests', 'node_modules'],
                ],
            ],
        ]);
    }

    public static function getNameByPackages(string $vendor): string
    {
        if (is_dir($vendor . IbexaSystemInfoCollector::COMMERCE_PACKAGES[0])) {
            $name = IbexaSystemInfo::PRODUCT_NAME_VARIANTS['commerce'];
        } elseif (is_dir($vendor . IbexaSystemInfoCollector::EXPERIENCE_PACKAGES[0])) {
            $name = IbexaSystemInfo::PRODUCT_NAME_VARIANTS['experience'];
        } elseif (is_dir($vendor . IbexaSystemInfoCollector::CONTENT_PACKAGES[0])) {
            $name = IbexaSystemInfo::PRODUCT_NAME_VARIANTS['content'];
        } else {
            $name = IbexaSystemInfo::PRODUCT_NAME_OSS;
        }

        return $name;
    }
}

class_alias(IbexaSystemInfoExtension::class, 'EzSystems\EzSupportToolsBundle\DependencyInjection\EzSystemsEzSupportToolsExtension');
