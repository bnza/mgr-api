<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class IsStrongPassword extends Assert\Compound
{
    protected function getConstraints(array $options): array
    {

        return [
            new Assert\NotBlank([
                'message' => 'Password cannot be blank.',
            ]),
            new Assert\Length([
                'min' => 8,
                'max' => 20,
                'minMessage' => 'Password must be at least {{ limit }} characters long.',
                'maxMessage' => 'Password cannot be longer than {{ limit }} characters.',
            ]),
            new Assert\Regex([
                'pattern' => '/[A-Z]/',
                'message' => 'Password must contain at least one uppercase letter.',
            ]),
            new Assert\Regex([
                'pattern' => '/[a-z]/',
                'message' => 'Password must contain at least one lowercase letter.',
            ]),
            new Assert\Regex([
                'pattern' => '/\d/',
                'message' => 'Password must contain at least one digit.',
            ]),
            new Assert\Regex([
                'pattern' => '/[\W_]/',
                'message' => 'Password must contain at least one special character.',
            ]),
        ];
    }
}
