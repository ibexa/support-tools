<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\SystemInfo\View;

use Ibexa\Bundle\SystemInfo\SystemInfo\Collector\SystemInfoCollector;
use Ibexa\Bundle\SystemInfo\SystemInfo\SystemInfoCollectorRegistry;
use Ibexa\Bundle\SystemInfo\SystemInfo\Value\SystemInfo;
use Ibexa\Bundle\SystemInfo\View\SystemInfoViewBuilder;
use Ibexa\Core\MVC\Symfony\View\Configurator;
use PHPUnit\Framework\TestCase;

class SystemInfoViewBuilderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Core\MVC\Symfony\View\Configurator
     */
    private Configurator $configuratorMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\SystemInfo\SystemInfo\SystemInfoCollectorRegistry
     */
    private SystemInfoCollectorRegistry $registryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\SystemInfo\SystemInfo\Collector\SystemInfoCollector
     */
    private SystemInfoCollector $collectorMock;

    public function testMatches(): void
    {
        $builder = new SystemInfoViewBuilder($this->getConfiguratorMock(), $this->getRegistryMock());
        self::assertTrue($builder->matches('ibexa.support_tools.view.controller::viewInfoAction'));
    }

    public function testNotMatches(): void
    {
        $builder = new SystemInfoViewBuilder($this->getConfiguratorMock(), $this->getRegistryMock());
        self::assertFalse($builder->matches('service::someAction'));
    }

    public function testBuildView(): void
    {
        $builder = new SystemInfoViewBuilder(
            $this->getConfiguratorMock(),
            $this->getRegistryMock()
        );

        $systemInfo = $this->createMock(SystemInfo::class);

        $this->getRegistryMock()
            ->method('getItem')
            ->with('test')
            ->will(self::returnValue($this->getCollectorMock()));

        $this->getCollectorMock()
            ->method('collect')
            ->will(self::returnValue($systemInfo));

        $view = $builder->buildView(['systemInfoIdentifier' => 'test', 'viewType' => 'test']);
        self::assertSame($view->getInfo(), $systemInfo);
        self::assertEquals($view->getViewType(), 'test');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Core\MVC\Symfony\View\Configurator
     */
    protected function getConfiguratorMock(): Configurator
    {
        $this->configuratorMock ??= $this->createMock(Configurator::class);

        return $this->configuratorMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\SystemInfo\SystemInfo\SystemInfoCollectorRegistry
     */
    protected function getRegistryMock(): SystemInfoCollectorRegistry
    {
        $this->registryMock ??= $this->createMock(
            SystemInfoCollectorRegistry::class
        );

        return $this->registryMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&\Ibexa\Bundle\SystemInfo\SystemInfo\Collector\SystemInfoCollector
     */
    protected function getCollectorMock(): SystemInfoCollector
    {
        $this->collectorMock ??= $this->createMock(SystemInfoCollector::class);

        return $this->collectorMock;
    }
}
