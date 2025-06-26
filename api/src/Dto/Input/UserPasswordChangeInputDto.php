<?php

namespace App\Dto\Input;

use App\Validator\IsStrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

class UserPasswordChangeInputDto
{
    #[Assert\NotBlank(groups: ['validation:user:change-password'])]
    public ?string $oldPassword;

    #[Assert\NotBlank(groups: ['validation:user:change-password'])]
    #[IsStrongPassword(groups: ['validation:user:change-password'])]
    public ?string $plainPassword;

    #[Assert\NotBlank(groups: ['validation:user:change-password'])]
    #[Assert\EqualTo(propertyPath: 'plainPassword', groups: ['validation:user:change-password'])]
    public ?string $repeatPassword;

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
}
