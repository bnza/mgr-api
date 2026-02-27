<?php

namespace App\Service\Validator\SameSite;

use App\Entity\Data\Join\SampleStratigraphicUnit;

class SampleStratigraphicUnitBelongToSameSite implements JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool
    {
        return $object instanceof SampleStratigraphicUnit;
    }

    public function __invoke(object $object): bool
    {
        if (!$this->supports($object)) {
            return false;
        }

        /** @var SampleStratigraphicUnit $contextSample */
        $contextSample = $object;

        $sample = $contextSample->getSample();
        $stratigraphicUnit = $contextSample->getStratigraphicUnit();

        if (null === $sample || null === $stratigraphicUnit) {
            return false;
        }

        $sampleSite = $sample->getSite();
        $contextSite = $stratigraphicUnit->getSite();

        return $sampleSite->getId() === $contextSite->getId();
    }
}
