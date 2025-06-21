<?php

namespace App\Tests\PHPUnit\Extensions;

use App\Tests\PHPUnit\Subscribers\VichUploaderTestRunnerFinishedSubscriber;
use App\Tests\PHPUnit\Subscribers\VichUploaderTestRunnerStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class VichUploaderExtension implements Extension
{
    #[\Override]
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters,
    ): void {
        $facade->registerSubscriber(
            new VichUploaderTestRunnerStartedSubscriber()
        );
        $facade->registerSubscriber(
            new VichUploaderTestRunnerFinishedSubscriber()
        );
    }
}
