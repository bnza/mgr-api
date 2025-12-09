<?php

namespace App\Service\CollectionVoter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

interface CollectionVoterInterface
{
    public function vote(array $context, TokenInterface $token): bool;
}
