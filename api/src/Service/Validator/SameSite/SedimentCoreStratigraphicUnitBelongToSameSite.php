<?php

namespace App\Service\Validator\SameSite;

use App\Entity\Data\Join\SedimentCoreDepth;
use App\Entity\Data\Site;

class SedimentCoreStratigraphicUnitBelongToSameSite implements JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool
    {
        return $object instanceof SedimentCoreDepth;
    }

    public function __invoke(object $object): Site|false
    {
        if (!$this->supports($object)) {
            return false;
        }

        /** @var SedimentCoreDepth $object */
        $sedimentCore = $object->getSedimentCore();
        $stratigraphicUnit = $object->getStratigraphicUnit();

        if (null === $sedimentCore || null === $stratigraphicUnit) {
            return false;
        }

        $sedimentCoreSite = $sedimentCore->getSite();
        $contextSite = $stratigraphicUnit->getSite();

        if ($sedimentCoreSite->getId() !== $contextSite->getId()) {
            return false;
        }

        return $sedimentCoreSite;
    }
}
