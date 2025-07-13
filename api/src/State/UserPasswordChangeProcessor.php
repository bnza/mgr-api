<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Input\UserPasswordChangeInputDto;
use App\Entity\Auth\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordChangeProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $repository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $user = null;
        if ($data instanceof UserPasswordChangeInputDto) {
            $user = $this->getCurrentUser();
            if ($user && !$this->passwordHasher->isPasswordValid($user, $data->oldPassword)) {
                throw new MissingTokenException('Invalid password.');
            }
        }

        if ($data instanceof User) {
            $user = $data;
        }

        if (!$user instanceof User) {
            return null;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->getPlainPassword()));

        return $this->persistProcessor->process($user, $operation, $uriVariables, $context);
    }

    private function getCurrentUser(): ?User
    {
        $currentUser = $this->security->getUser();

        return $this->repository->findOneBy(['email' => $currentUser->getUserIdentifier()]);
    }
}
