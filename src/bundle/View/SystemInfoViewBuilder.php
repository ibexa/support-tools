<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\SystemInfo\View;

use Ibexa\Bundle\SystemInfo\SystemInfo\Collector\SystemInfoCollector;
use Ibexa\Bundle\SystemInfo\SystemInfo\Exception\SystemInfoException;
use Ibexa\Bundle\SystemInfo\SystemInfo\SystemInfoCollectorRegistry;
use Ibexa\Bundle\SystemInfo\SystemInfo\Value\InvalidSystemInfo;
use Ibexa\Core\MVC\Symfony\View\Builder\ViewBuilder;
use Ibexa\Core\MVC\Symfony\View\Configurator;
use Ibexa\Core\MVC\Symfony\View\View;

class SystemInfoViewBuilder implements ViewBuilder
{
    /**
     * @var \Ibexa\Core\MVC\Symfony\View\Configurator
     */
    private $viewConfigurator;

    /**
     * System info collector registry.
     *
     * @var \Ibexa\Bundle\SystemInfo\SystemInfo\SystemInfoCollectorRegistry
     */
    private $registry;

    public function __construct(Configurator $viewConfigurator, SystemInfoCollectorRegistry $registry)
    {
        $this->viewConfigurator = $viewConfigurator;
        $this->registry = $registry;
    }

    public function matches($argument): bool
    {
        return $argument === 'ibexa.support_tools.view.controller::viewInfoAction';
    }

    /**
     * @param array<string, string> $parameters
     *
     * @return \Ibexa\Bundle\SystemInfo\View\SystemInfoView
     */
    public function buildView(array $parameters): View
    {
        $collector = $this->getCollector($parameters['systemInfoIdentifier']);
        $view = new SystemInfoView(null, [], $parameters['viewType']);

        try {
            $collectedData = $collector->collect();
        } catch (SystemInfoException $e) {
            $collectedData = new InvalidSystemInfo();
            $collectedData->errorMessage = $e->getMessage();
        }

        $view->setInfo($collectedData);
        $this->viewConfigurator->configure($view);

        return $view;
    }

    /**
     * @param string $identifier A SystemInfo collector identifier (php, hardware...)
     */
    private function getCollector(string $identifier): SystemInfoCollector
    {
        return $this->registry->getItem($identifier);
    }
}
