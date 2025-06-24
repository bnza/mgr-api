<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsValidRole extends Constraint
{
    public string $message = 'Invalid role: {{  string }}';
}
