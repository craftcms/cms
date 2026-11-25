<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use craft\db\Command;

/**
 * A [[Command]] that records the SQL of every statement it executes.
 *
 * Tests swap this in via [[\yii\db\Connection::$commandClass]] when they need to
 * assert on the statements a save issued. The query log isn’t an option here:
 * Codeception replaces the Yii logger with one that explicitly drops
 * `yii\db\Command` categories.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class RecordingCommand extends Command
{
    /**
     * @var string[] The SQL of each statement executed since recording began.
     */
    private static array $executed = [];

    /**
     * @var string[] The SQL of each statement executed or queried since recording began.
     */
    private static array $statements = [];

    /**
     * Discards anything recorded so far.
     */
    public static function startRecording(): void
    {
        self::$executed = [];
        self::$statements = [];
    }

    /**
     * Returns the SQL of each statement executed since [[startRecording()]], and resets.
     *
     * @return string[]
     */
    public static function stopRecording(): array
    {
        $executed = self::$executed;
        self::$executed = [];
        self::$statements = [];
        return $executed;
    }

    /**
     * Returns the SQL of each statement executed or queried since [[startRecording()]], and resets.
     *
     * @return string[]
     */
    public static function stopRecordingStatements(): array
    {
        $statements = self::$statements;
        self::$executed = [];
        self::$statements = [];
        return $statements;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        self::$executed[] = $this->getSql();
        self::$statements[] = $this->getSql();
        return parent::execute();
    }

    /**
     * @inheritdoc
     */
    protected function queryInternal($method, $fetchMode = null)
    {
        self::$statements[] = $this->getSql();
        return parent::queryInternal($method, $fetchMode);
    }
}
