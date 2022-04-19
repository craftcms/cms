<?php

namespace craft\migrations;

use Composer\Semver\Semver;
use Craft;
use craft\db\Migration;
use craft\queue\QueueInterface;
use Throwable;

/**
 * m220317_174250_release_all_jobs migration.
 */
class m220317_174250_release_all_jobs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Only do this if we're coming from Craft 3
        if (Semver::satisfies(Craft::$app->getInfo()->version, '<4.0')) {
            $warning = null;
            $queue = Craft::$app->getQueue();

            if ($queue instanceof QueueInterface) {
                try {
                    $queue->releaseAll();
                } catch (Throwable $e) {
                    $warning = $e->getMessage();
                }
            } else {
                $warning = sprintf('%s doesn\'t implement %s', get_class($queue), QueueInterface::class);
            }

            if ($warning) {
                Craft::warning("Unable to release all queue jobs: $warning", __METHOD__);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
