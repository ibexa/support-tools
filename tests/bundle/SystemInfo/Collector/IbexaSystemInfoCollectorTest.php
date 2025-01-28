<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\SystemInfo\SystemInfo\Collector;

use Ibexa\Bundle\SystemInfo\SystemInfo\Collector\IbexaSystemInfoCollector;
use Ibexa\Bundle\SystemInfo\SystemInfo\Collector\JsonComposerLockSystemInfoCollector;
use Ibexa\Bundle\SystemInfo\SystemInfo\Value\IbexaSystemInfo;
use Ibexa\Contracts\Core\Ibexa;
use Ibexa\SystemInfo\VersionStability\VersionStabilityChecker;
use PHPUnit\Framework\TestCase;

class IbexaSystemInfoCollectorTest extends TestCase
{
    /** @var \Ibexa\SystemInfo\VersionStability\VersionStabilityChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $versionStabilityChecker;

    public function setUp(): void
    {
        $this->versionStabilityChecker = $this->createMock(VersionStabilityChecker::class);
    }

    public function testCollect(): void
    {
        $composerCollector = new JsonComposerLockSystemInfoCollector(
            $this->versionStabilityChecker,
            __DIR__ . '/_fixtures/composer.lock',
            __DIR__ . '/_fixtures/composer.json'
        );

        $systemInfoCollector = new IbexaSystemInfoCollector(
            $composerCollector,
            dirname(__DIR__, 5)
        );
        $systemInfo = $systemInfoCollector->collect();
        self::assertSame(IbexaSystemInfo::PRODUCT_NAME_OSS, $systemInfo->name);
        self::assertSame(Ibexa::VERSION, $systemInfo->release);

        // Test that information from the composer.json file is correctly extracted
        self::assertSame('dev', $systemInfo->lowestStability);
    }

    public function testCollectWithInvalidComposerJson(): void
    {
        $composerCollector = new JsonComposerLockSystemInfoCollector(
            $this->versionStabilityChecker,
            __DIR__ . '/_fixtures/corrupted_composer.lock',
            __DIR__ . '/_fixtures/corrupted_composer.json'
        );

        $systemInfoCollector = new IbexaSystemInfoCollector(
            $composerCollector,
            dirname(__DIR__, 5)
        );
        $systemInfo = $systemInfoCollector->collect();
        self::assertNull($systemInfo->lowestStability);
    }
}
