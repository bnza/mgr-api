<?php

namespace App\Service\Validator\SameSite;

use App\Entity\Data\Join\ContextSample;
use App\Entity\Data\Site;

class ContextSampleBelongToSameSite implements JoinResourceBelongToSameSiteInterface
{
    public function supports(object $object): bool
    {
        return $object instanceof ContextSample;
    }

    public function __invoke(object $object): Site|false
    {
        if (!$this->supports($object)) {
            return false;
        }

        /** @var ContextSample $contextSample */
        $contextSample = $object;

        $sample = $contextSample->getSample();
        $context = $contextSample->getContext();

        if (null === $sample || null === $context) {
            return false;
        }

        $sampleSite = $sample->getSite();
        $contextSite = $context->getSite();

        if ($sampleSite->getId() !== $contextSite->getId()) {
            return false;
        }

        return $sampleSite;
    }
}
