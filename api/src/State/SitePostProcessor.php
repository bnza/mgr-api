<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Auth\SiteUserPrivilege;
use App\Entity\Auth\User;
use App\Entity\Data\Site;
use App\Security\Utils\SitePrivilegeManager;
use App\Security\Utils\SitePrivileges;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class SitePostProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly SitePrivilegeManager $sitePrivilegeManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if ($data instanceof Site && 'POST' === $operation->getMethod()) {
            if ($user instanceof User) {
                $data->setCreatedBy($user);
            }
        }

        $site = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        if ($site instanceof Site) {
            $siteUserPrivilege = new SiteUserPrivilege();
            $siteUserPrivilege->setSite($site);
            $siteUserPrivilege->setUser($user);
            $this->sitePrivilegeManager->grantPrivilege($siteUserPrivilege, SitePrivileges::Editor);

            $this->entityManager->persist($siteUserPrivilege);
            $this->entityManager->flush();
        }

        return $site;
    }
}
