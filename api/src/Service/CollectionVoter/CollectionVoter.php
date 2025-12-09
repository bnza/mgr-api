<?php

namespace App\Service\CollectionVoter;

use App\Entity\Data\Analysis;
use App\Repository\AnalysisRepository;
use App\Security\RoleProviderInterface;
use App\Security\Utils\SitePrivilegeManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

final class CollectionVoter
{
    private array $voters = [];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly RoleProviderInterface $roleProvider,
        private readonly SitePrivilegeManager $sitePrivilegeManager,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function getVoter(string $resourceClass): ?CollectionVoterInterface
    {
        if (!array_key_exists($resourceClass, $this->voters)) {
            $baseName = str_replace('App\\Entity\\', '', $resourceClass);
            $collectionVoterClass = __NAMESPACE__."\\Voter\\{$baseName}CollectionVoter";
            if (!class_exists($collectionVoterClass)) {
                $this->logger->warning("Unexistent voter class $collectionVoterClass");

                return null;
            }
            $this->voters[$resourceClass] = new $collectionVoterClass($this->accessDecisionManager, $this->roleProvider, $this->sitePrivilegeManager, $this->security, $this->entityManager);
        }

        return $this->voters[$resourceClass];
    }

    protected function userHasAnalysis(TokenInterface $token): bool
    {
        /** @var AnalysisRepository $repository */
        $repository = $this->entityManager->getRepository(Analysis::class);

        return $repository->userHasAnalysis($token->getUser());
    }

    public function vote(array $context): bool
    {
        $resourceClass = $context['resource_class'];
        // Merge only when we know the resource class (collection of API resources)
        if (!isset($resourceClass)) {
            $this->logger->warning('Unknown resource class');

            return false;
        }

        $token = $this->security->getToken();

        if (!$token) {
            return false;
        }

        return $this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])
        || $this->getVoter($resourceClass)?->vote($context, $token) ?? false;
    }
}
