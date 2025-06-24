<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\User\UserPasswordChangeInputDto;
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
        if (!$data instanceof UserPasswordChangeInputDto) {
            throw new \InvalidArgumentException(
                "Invalid input data type. Expected ".UserPasswordChangeInputDto::class.", got ".get_class($data)
            );
        }
        $currentUser = $this->security->getUser();

        $user = $this->repository->findOneBy(['email' => $currentUser->getUserIdentifier()]);

        if (!$user instanceof User) {
            return null;
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data->oldPassword)) {
            throw new MissingTokenException('Invalid password.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data->newPassword));

        return $this->persistProcessor->process($user, $operation, $uriVariables, $context);
    }
}
