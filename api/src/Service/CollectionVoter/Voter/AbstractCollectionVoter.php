<?php

namespace App\Service\CollectionVoter\Voter;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Security\RoleProviderInterface;
use App\Security\Utils\SitePrivilegeManager;
use App\Service\CollectionVoter\CollectionVoterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

abstract readonly class AbstractCollectionVoter implements CollectionVoterInterface
{
    abstract protected function voteOnSubCollection(object $parent, TokenInterface $token): bool;

    abstract protected function voteOnWholeCollection(string $context, TokenInterface $token): bool;

    public function __construct(
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected RoleProviderInterface $roleProvider,
        protected SitePrivilegeManager $sitePrivilegeManager,
        protected Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    protected function getParent(array $context): ?object
    {
        if (!isset($context['operation']) || !($context['operation'] instanceof GetCollection)) {
            return null;
        }

        $operation = $context['operation'];

        $uriVariables = $operation->getUriVariables();

        if (!isset($uriVariables['parentId']) || !($uriVariables['parentId'] instanceof Link)) {
            return null;
        }

        if (!isset($context['uri_variables']['parentId'])) {
            return null;
        }

        return $this->entityManager->getReference($uriVariables['parentId']->getFromClass(), $context['uri_variables']['parentId']);
    }

    public function vote(array $context, TokenInterface $token): bool
    {
        $parent = $this->getParent($context);

        return $parent
            ? $this->voteOnSubCollection($parent, $token)
            : $this->voteOnWholeCollection($context['resource_class'], $token);
    }
}
