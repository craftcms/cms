<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\services;


use Codeception\Test\Unit;
use craft\services\EntryRevisions;
use craftunit\fixtures\EntriesFixture;
use UnitTester;

/**
 * Unit tests for the garbage collector service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class EntryRevisionsTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester $tester
     */
    protected $tester;

    /**
     * @var EntryRevisions $entryRevisions
     */
    protected $entryRevisions;

    // Fixtures
    // =========================================================================

    /**
     * @return array
     */
    public function _fixtures() : array
    {
        return [
            'entries' => [
                'class' => EntriesFixture::class
            ]
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     *
     */
    public function testPublishDraftPublishesDraft()
    {

    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();
        $this->entryRevisions = \Craft::$app->getEntryRevisions();
    }
}
