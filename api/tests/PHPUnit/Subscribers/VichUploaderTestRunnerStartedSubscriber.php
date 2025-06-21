<?php

namespace App\Tests\PHPUnit\Subscribers;

use App\Tests\Utils\VichUploaderTestTrait;
use PHPUnit\Event\TestRunner\Started;
use PHPUnit\Event\TestRunner\StartedSubscriber;

class VichUploaderTestRunnerStartedSubscriber implements StartedSubscriber
{
    use VichUploaderTestTrait;

    #[\Override]
    public function notify(Started $event): void
    {
        $this->createUploadDirectoryTree();
    }
}
