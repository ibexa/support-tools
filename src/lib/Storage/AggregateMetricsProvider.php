<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\SystemInfo\Storage;

use Ibexa\Bundle\SystemInfo\SystemInfo\Exception\MetricsNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final class AggregateMetricsProvider implements MetricsProvider
{
    /** @var \Symfony\Component\DependencyInjection\ServiceLocator */
    private $metricsLocator;

    public function __construct(ServiceLocator $metrics)
    {
        $this->metricsLocator = $metrics;
    }

    /**
     * @throws \Ibexa\Bundle\SystemInfo\SystemInfo\Exception\MetricsNotFoundException
     */
    public function provideMetrics(string $identifier): Metrics
    {
        try {
            return $this->metricsLocator->get($identifier);
        } catch (ServiceNotFoundException $e) {
            throw new MetricsNotFoundException($identifier, $e);
        }
    }
}

class_alias(AggregateMetricsProvider::class, 'EzSystems\EzSupportTools\Storage\AggregateMetricsProvider');
