<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:test-parameters')]
class TestParametersCommand extends Command
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln('Testing parameter resolution:');
            $output->writeln('USER_BASE_PW: '.$this->parameterBag->get('app.alice.parameters.user_base_pw'));
            $output->writeln('USER_EDITOR_PW: '.$this->parameterBag->get('app.alice.parameters.user_editor_pw'));
            $output->writeln('USER_ADMIN_PW: '.$this->parameterBag->get('app.alice.parameters.user_admin_pw'));
            $output->writeln('USER_GEO_PW: '.$this->parameterBag->get('app.alice.parameters.user_geo_pw'));
        } catch (\Exception $e) {
            $output->writeln('Error: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
