<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use Codeception\Test\Unit;
use craft\elements\Entry;
use craft\test\TestCase;
use crafttests\fixtures\EntryWithMatrixFixture;
use yii\base\NotSupportedException;

/**
 * Unit tests for App
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4
 */
class EagerLoadingTest extends TestCase
{
    public function _fixtures(): array
    {
        return [
            'entry-with-matrix' => [
                'class' => EntryWithMatrixFixture::class,
            ],
        ];
    }

    /**
     * @throws NotSupportedException
     */
    public function testEagerLoading(): void
    {
        // getting the entry
        $entry = Entry::find()
            ->title('Matrix with relational field')
            ->with([
                'relatedEntry',
                'matrixSecond',
                'matrixSecond.bBlock:entriesSubfield',
                'matrixFirst',
            ])
            ->one();

        self::assertNotNull($entry);

        // check if simple relational field e.g. related entry was eager loaded
        self::assertNotEmpty($entry->getEagerLoadedElements('relatedEntry'));

        // check if matrix field was eager loaded
        $matrixSecond = $entry->getEagerLoadedElements('matrixSecond');
        self::assertNotEmpty($matrixSecond);

        // check if relational field inside a matrix field was eager loaded
        self::assertNotEmpty($matrixSecond[0]->getEagerLoadedElements('entriesSubfield'));

        // check if eager loading field that's not part of the layout returns empty result
        self::assertEmpty($matrixSecond[1]->getEagerLoadedElements('entriesSubfield'));
        self::assertEmpty($entry->getEagerLoadedElements('matrixFirst'));
    }
}
