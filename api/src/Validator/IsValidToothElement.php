<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class IsValidToothElement extends Constraint
{
    public string $message = 'Invalid tooth element. Expected bone code to be one of: "MAX", "N", but got "{{ code }}".';
    public string $typeMessage = 'Expected instance of App\Entity\Vocabulary\Zoo\Bone, but got {{ type }}.';
}
