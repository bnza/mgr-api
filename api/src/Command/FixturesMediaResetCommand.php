<?php

namespace App\Command;

use App\Service\FixturesMediaReset;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'app:fixtures:reset-media', description: 'Reset media fixture files for test and dev purposes')]
class FixturesMediaResetCommand extends Command
{
    public function __construct(private readonly FixturesMediaReset $loader, ?string $name = null)
    {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (
            $input->isInteractive()
            && !$this->askConfirmation(
                $input,
                $output,
                sprintf(
                    '<question>Careful, all files in %s will be replaced with files in %s. Do you want to continue y/N ?</question>',
                    $this->loader->getDestinationPath(),
                    $this->loader->getSourcePath()
                ),
                false
            )
        ) {
            return self::SUCCESS;
        }

        return $this->loader->load();
    }

    private function askConfirmation(
        InputInterface $input,
        OutputInterface $output,
        string $question,
        bool $default,
    ): bool {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion($question, $default);

        return (bool) $questionHelper->ask($input, $output, $question);
    }
}
