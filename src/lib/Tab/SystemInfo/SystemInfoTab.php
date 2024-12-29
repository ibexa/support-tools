<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\SystemInfo\Tab\SystemInfo;

use Ibexa\Contracts\AdminUi\Tab\AbstractControllerBasedTab;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SystemInfoTab extends AbstractControllerBasedTab
{
    /** @var string */
    protected $tabIdentifier;

    /** @var string */
    protected $collectorIdentifier;

    /**
     * @param \Twig\Environment $twig
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator
     * @param \Symfony\Bridge\Twig\Extension\HttpKernelRuntime $httpKernelRuntime
     * @param string $tabIdentifier
     * @param string $collectorIdentifier
     */
    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        HttpKernelRuntime $httpKernelRuntime,
        string $tabIdentifier,
        string $collectorIdentifier
    ) {
        parent::__construct($twig, $translator, $httpKernelRuntime);

        $this->tabIdentifier = $tabIdentifier;
        $this->collectorIdentifier = $collectorIdentifier;
    }

    public function getControllerReference(array $parameters): ControllerReference
    {
        return new ControllerReference('ibexa.support_tools.view.controller::viewInfoAction', [
            'systemInfoIdentifier' => $this->collectorIdentifier,
            'viewType' => 'pjax_tab',
        ]);
    }

    public function getIdentifier(): string
    {
        return $this->tabIdentifier;
    }

    public function getName(): string
    {
        return /** @Ignore */$this->translator->trans(sprintf('tab.name.%s', $this->tabIdentifier), [], 'ibexa_systeminfo');
    }
}
