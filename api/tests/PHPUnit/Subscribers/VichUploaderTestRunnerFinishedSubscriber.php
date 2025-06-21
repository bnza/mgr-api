<?php

namespace App\Tests\PHPUnit\Subscribers;

use App\Tests\Utils\VichUploaderTestTrait;
use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;

class VichUploaderTestRunnerFinishedSubscriber implements FinishedSubscriber
{
    use VichUploaderTestTrait;

    #[\Override]
    public function notify(Finished $event): void
    {
        $this->removeUploadDirectoryTree();
    }
}
