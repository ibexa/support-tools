<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\SystemInfo\SystemInfo\Collector;

use DateTime;
use Ibexa\Bundle\SystemInfo\DependencyInjection\IbexaSystemInfoExtension;
use Ibexa\Bundle\SystemInfo\SystemInfo\Exception\ComposerFileValidationException;
use Ibexa\Bundle\SystemInfo\SystemInfo\Exception\ComposerLockFileNotFoundException;
use Ibexa\Bundle\SystemInfo\SystemInfo\Value\ComposerSystemInfo;
use Ibexa\Bundle\SystemInfo\SystemInfo\Value\IbexaSystemInfo;
use Ibexa\Contracts\Core\Ibexa;
use Ibexa\SystemInfo\Value\Stability;

/**
 * Collects information about the Ibexa installation.
 *
 * @internal This class will greatly change in the future and should not be used as an API, planned:
 *           - Get most of this information off updates.ibexa.co
 *           - Probably run this as a nightly cronjob to gather summary info
 *           - Be able to provide warnings to admins when something (config/system setup) is not optimal
 *           - Be able to give information if important updates are available to the installation
 *           - Or be able to tell if installation is greatly outdated
 *           - Be able to give heads up when installation is approaching its End of Life.
 */
class IbexaSystemInfoCollector implements SystemInfoCollector
{
    /**
     * Estimated release dates for given releases.
     *
     * Mainly for usage for trial to calculate TTL expiry.
     */
    public const RELEASES = [
        '2.5' => '2019-03-29T16:59:59+00:00',
        '3.0' => '2020-04-02T23:59:59+00:00',
        '3.1' => '2020-07-15T23:59:59+00:00',
        '3.2' => '2020-10-23T23:59:59+00:00',
        '3.3' => '2021-01-18T23:59:59+00:00',
    ];

    /**
     * Dates for when releases are considered End of Maintenance.
     *
     * Open source releases are considered End of Life when this date is reached.
     *
     * @Note: Only Enterprise/Commerce installations receive fixes for security
     *        issues before the issues are disclosed. Also, be aware the link
     *        below is covering Enterprise/Commerce releases, length of
     *        maintenance for LTS releases may not be as long for open source
     *        releases as it depends on community maintenance efforts.
     *
     * @see: https://support.ibexa.co/Public/Service-Life
     */
    public const EOM = [
        '2.5' => '2022-03-29T23:59:59+00:00',
        '3.0' => '2020-07-10T23:59:59+00:00',
        '3.1' => '2020-11-30T23:59:59+00:00',
        '3.2' => '2021-02-28T23:59:59+00:00',
        '3.3' => '2023-12-30T23:59:59+00:00',
    ];

    /**
     * Dates for when Enterprise/Commerce installations are considered End of Life.
     *
     * Meaning, when they stop receiving security fixes and support.
     *
     * @see: https://support.ibexa.co/Public/Service-Life
     */
    public const EOL = [
        '2.5' => '2024-03-29T23:59:59+00:00',
        '3.0' => '2020-08-31T23:59:59+00:00',
        '3.1' => '2021-01-30T23:59:59+00:00',
        '3.2' => '2021-04-30T23:59:59+00:00',
        '3.3' => '2025-12-30T23:59:59+00:00',
    ];

    /**
     * Vendors we watch for stability (and potentially more).
     */
    public const PACKAGE_WATCH_REGEX = '/^(doctrine|ezsystems|silversolutions|symfony)\//';

    /**
     * Packages that identify installation as "Content".
     */
    public const CONTENT_PACKAGES = [
        'ibexa/workflow',
    ];

    public const EXPERIENCE_PACKAGES = [
        'ibexa/page-builder',
        'ezsystems/landing-page-fieldtype-bundle',
    ];

    /**
     * Packages that identify installation as "Enterprise".
     *
     * @deprecated since Ibexa DXP 3.3. Rely either on <code>IbexaSystemInfoCollector::EXPERIENCE_PACKAGES</code>
     * or <code>IbexaSystemInfoCollector::CONTENT_PACKAGES</code>.
     */
    public const ENTERPRISE_PACKAGES = [
        'ibexa/page-builder',
        'ezsystems/flex-workflow',
        'ezsystems/landing-page-fieldtype-bundle',
    ];

    /**
     * Packages that identify installation as "Commerce".
     */
    public const COMMERCE_PACKAGES = [
        'ibexa/commerce-transaction',
    ];

    /**
     * @var \Ibexa\Bundle\SystemInfo\SystemInfo\Value\ComposerSystemInfo|null
     */
    private $composerInfo;

    /**
     * @var bool
     */
    private $debug;

    /** @var string */
    private $kernelProjectDir;

    /**
     * @param \Ibexa\Bundle\SystemInfo\SystemInfo\Collector\JsonComposerLockSystemInfoCollector|\Ibexa\Bundle\SystemInfo\SystemInfo\Collector\SystemInfoCollector $composerCollector
     * @param bool $debug
     */
    public function __construct(
        SystemInfoCollector $composerCollector,
        string $kernelProjectDir,
        bool $debug = false
    ) {
        try {
            $this->composerInfo = $composerCollector->collect();
        } catch (ComposerLockFileNotFoundException | ComposerFileValidationException $e) {
            // do nothing
        }
        $this->debug = $debug;
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * Collects information about the Ibexa distribution and version.
     *
     * @throws \Exception
     *
     * @return \Ibexa\Bundle\SystemInfo\SystemInfo\Value\IbexaSystemInfo
     */
    public function collect(): IbexaSystemInfo
    {
        $vendorDir = sprintf('%s/vendor/', $this->kernelProjectDir);

        $ibexa = new IbexaSystemInfo([
            'debug' => $this->debug,
            'name' => IbexaSystemInfoExtension::getNameByPackages($vendorDir),
        ]);

        $this->setReleaseInfo($ibexa);
        $this->extractComposerInfo($ibexa);

        return $ibexa;
    }

    /**
     * @throws \Exception
     */
    private function setReleaseInfo(IbexaSystemInfo $ibexa): void
    {
        $ibexa->release = Ibexa::VERSION;
        // try to extract version number, but prepare for unexpected string
        [$majorVersion, $minorVersion] = array_pad(explode('.', $ibexa->release), 2, '');
        $ibexaRelease = "{$majorVersion}.{$minorVersion}";

        if (isset(self::EOM[$ibexaRelease])) {
            $ibexa->isEndOfMaintenance = strtotime(self::EOM[$ibexaRelease]) < time();
        }

        if (isset(self::EOL[$ibexaRelease])) {
            $ibexa->isEndOfLife = strtotime(self::EOL[$ibexaRelease]) < time();
        }

        $ibexa->endOfMaintenanceDate = $this->getEOMDate($ibexaRelease);
        $ibexa->endOfLifeDate = $this->getEOLDate($ibexaRelease);
    }

    private function extractComposerInfo(IbexaSystemInfo $ibexa): void
    {
        if ($this->composerInfo === null) {
            return;
        }

        // BC (deprecated property)
        $ibexa->composerInfo = ['minimumStability' => $this->composerInfo->minimumStability];

        $dxpPackages = array_merge(
            self::CONTENT_PACKAGES,
            self::EXPERIENCE_PACKAGES,
            self::COMMERCE_PACKAGES
        );
        $ibexa->isEnterprise = self::hasAnyPackage($this->composerInfo, $dxpPackages);
        $ibexa->stability = $ibexa->lowestStability = self::getStability($this->composerInfo);
    }

    /**
     * @throws \Exception
     */
    private function getEOMDate(string $ibexaRelease): ?DateTime
    {
        return isset(self::EOM[$ibexaRelease]) ?
            new DateTime(self::EOM[$ibexaRelease]) :
            null;
    }

    /**
     * @throws \Exception
     */
    private function getEOLDate(string $ibexaRelease): ?DateTime
    {
        return isset(self::EOL[$ibexaRelease]) ?
            new DateTime(self::EOL[$ibexaRelease]) :
            null;
    }

    private static function getStability(ComposerSystemInfo $composerInfo): string
    {
        $stabilityFlags = array_flip(Stability::STABILITIES);

        // Root package stability
        $stabilityFlag = $composerInfo->minimumStability !== null ?
            $stabilityFlags[$composerInfo->minimumStability] :
            $stabilityFlags['stable'];

        // Check if any of the watched packages has lower stability than root
        foreach ($composerInfo->packages as $name => $package) {
            if (!preg_match(self::PACKAGE_WATCH_REGEX, $name)) {
                continue;
            }

            if ($package->stability === 'stable' || $package->stability === null) {
                continue;
            }

            if ($stabilityFlags[$package->stability] > $stabilityFlag) {
                $stabilityFlag = $stabilityFlags[$package->stability];
            }
        }

        return Stability::STABILITIES[$stabilityFlag];
    }

    private static function hasAnyPackage(
        ComposerSystemInfo $composerInfo,
        array $packageNames
    ): bool {
        foreach ($packageNames as $packageName) {
            if (isset($composerInfo->packages[$packageName])) {
                return true;
            }
        }

        return false;
    }
}

class_alias(IbexaSystemInfoCollector::class, 'EzSystems\EzSupportToolsBundle\SystemInfo\Collector\IbexaSystemInfoCollector');
