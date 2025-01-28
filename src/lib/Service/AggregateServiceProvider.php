<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\SystemInfo\Service;

use Ibexa\Bundle\SystemInfo\SystemInfo\Exception\SystemInfoServiceNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final class AggregateServiceProvider implements ServiceProviderInterface
{
    /** @var \Symfony\Component\DependencyInjection\ServiceLocator<\Ibexa\SystemInfo\Service\ServiceInfo> */
    private ServiceLocator $serviceLocator;

    /**
     * @param \Symfony\Component\DependencyInjection\ServiceLocator<\Ibexa\SystemInfo\Service\ServiceInfo> $service
     */
    public function __construct(ServiceLocator $service)
    {
        $this->serviceLocator = $service;
    }

    /**
     * @throws \Ibexa\Bundle\SystemInfo\SystemInfo\Exception\SystemInfoServiceNotFoundException
     */
    public function getServiceType(string $identifier): string
    {
        try {
            return $this->serviceLocator->get($identifier)->getServiceType();
        } catch (ServiceNotFoundException $e) {
            throw new SystemInfoServiceNotFoundException($identifier, $e);
        }
    }
}
