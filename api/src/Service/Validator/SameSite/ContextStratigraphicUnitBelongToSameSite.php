<?php

namespace App\Service\Validator\SameSite;

use App\Entity\Data\Join\ContextStratigraphicUnit;
use App\Entity\Data\Site;

class ContextStratigraphicUnitBelongToSameSite implements JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool
    {
        return $object instanceof ContextStratigraphicUnit;
    }

    public function __invoke(object $object): Site|false
    {
        if (!$this->supports($object)) {
            return false;
        }

        /** @var ContextStratigraphicUnit $contextSu */
        $contextSu = $object;

        $stratigraphicUnit = $contextSu->getStratigraphicUnit();
        $context = $contextSu->getContext();

        if (null === $stratigraphicUnit || null === $context) {
            return false;
        }

        $suSite = $stratigraphicUnit->getSite();

        $contextSite = $context->getSite();

        if ($suSite->getId() !== $contextSite->getId()) {
            return false;
        }

        return $suSite;
    }
}
