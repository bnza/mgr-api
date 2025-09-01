<?php

namespace App\Service\Validator\SameSite;

use App\Entity\Data\Site;
use App\Entity\Data\View\StratigraphicUnitRelationshipView;

class StratigraphicUnitRelationshipBelongToSameSite implements JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool
    {
        return $object instanceof StratigraphicUnitRelationshipView;
    }

    public function __invoke(object $object): Site|false
    {
        if (!$this->supports($object)) {
            return false;
        }

        /** @var StratigraphicUnitRelationshipView $stratigraphicUnitRelationship */
        $stratigraphicUnitRelationship = $object;

        $lftSu = $stratigraphicUnitRelationship->getLftStratigraphicUnit();
        $rgtSu = $stratigraphicUnitRelationship->getRgtStratigraphicUnit();

        if (null === $lftSu || null === $rgtSu) {
            return false;
        }

        $rgtSite = $lftSu->getSite();
        $lftSite = $rgtSu->getSite();

        if ($rgtSite->getId() !== $lftSite->getId()) {
            return false;
        }

        return $rgtSite;
    }
}
