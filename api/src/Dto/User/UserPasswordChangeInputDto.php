<?php

namespace App\Dto\User;

use App\Validator\IsStrongPassword;
use Symfony\Component\Validator\Constraints as Assert;

class UserPasswordChangeInputDto
{
    #[Assert\NotBlank(groups: ['validation:user:change-password'])]
    public ?string $oldPassword;

    #[Assert\NotBlank(groups: ['validation:user:change-password'])]
    #[IsStrongPassword(groups: ['validation:user:change-password'])]
    public ?string $newPassword;

    #[Assert\NotBlank(groups: ['validation:user:change-password'])]
    #[Assert\EqualTo(propertyPath: 'newPassword', groups: ['validation:user:change-password'])]
    public ?string $repeatPassword;
}
