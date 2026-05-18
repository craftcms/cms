<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\_support\Extension;

use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use RuntimeException;

class FailOnIncompleteRun extends Extension
{
    protected static array $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::TEST_START => 'testStarted',
        Events::TEST_END => 'testEnded',
        Events::SUITE_AFTER => ['afterSuite', -1000],
    ];

    private ?string $activeTest = null;

    public function beforeSuite(SuiteEvent $event): void
    {
        $this->activeTest = null;
    }

    public function testStarted(TestEvent $event): void
    {
        $this->activeTest = $event->getTest()->getName();
    }

    public function testEnded(): void
    {
        $this->activeTest = null;
    }

    public function afterSuite(): void
    {
        if ($this->activeTest === null) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Codeception stopped before test "%s" completed.',
            $this->activeTest,
        ));
    }
}
