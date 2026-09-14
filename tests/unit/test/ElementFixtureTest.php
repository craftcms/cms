<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\test;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\fields\Matrix;
use craft\test\fixtures\elements\BaseContentFixture;
use craft\test\fixtures\elements\EntryFixture;
use craft\test\TestCase;
use crafttests\fixtures\EntryWithMatrixFixture;
use RuntimeException;
use yii\base\ModelEvent;

/**
 * Tests revision creation during element fixture saves.
 */
class ElementFixtureTest extends TestCase
{
    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'entries' => [
                'class' => EntryWithMatrixFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider revisionSettingsProvider
     * @param bool $contentFixture Whether to update an existing entry through a content fixture
     * @param bool $createRevisions Whether the fixture opts into revisions
     * @param bool $enableVersioning Whether the section enables revisions
     * @return void
     */
    public function testRevisionSettings(bool $contentFixture, bool $createRevisions, bool $enableVersioning): void
    {
        $fixture = $this->createFixture($contentFixture);
        if ($createRevisions) {
            $fixture->createRevisions = true;
        }
        $entry = $this->createEntry();
        $section = $entry->getSection();
        $originalVersioning = $section->enableVersioning;
        $elements = Craft::$app->getElements();

        try {
            if ($contentFixture) {
                $section->enableVersioning = false;
                self::assertTrue($elements->saveElement($entry));
                self::assertFalse($this->hasRevisions($entry));
                $entry->title = 'Updated fixture entry';
            }

            $section->enableVersioning = $enableVersioning;
            self::assertTrue($this->invokeMethod($fixture, 'saveElement', [$entry]));
            self::assertSame($enableVersioning, $section->enableVersioning);
            self::assertSame($createRevisions && $enableVersioning, $this->hasRevisions($entry));

            // Ordinary saves must regain the section's normal revision behavior.
            $entry->title = 'Saved outside the fixture';
            self::assertTrue($elements->saveElement($entry));
            self::assertSame($enableVersioning, $this->hasRevisions($entry));
        } finally {
            $section->enableVersioning = $originalVersioning;
        }
    }

    /**
     * @return array<string, array{bool, bool, bool}>
     */
    public static function revisionSettingsProvider(): array
    {
        return [
            'entry defaults to no revisions' => [false, false, true],
            'entry opts into revisions' => [false, true, true],
            'entry preserves disabled versioning' => [false, false, false],
            'entry opt-in respects disabled versioning' => [false, true, false],
            'content defaults to no revisions' => [true, false, true],
            'content opts into revisions' => [true, true, true],
            'content preserves disabled versioning' => [true, false, false],
            'content opt-in respects disabled versioning' => [true, true, false],
        ];
    }

    /**
     * @dataProvider failedSaveProvider
     * @param bool $contentFixture Whether to use a content fixture
     * @param bool $throw Whether saving throws instead of returning false
     * @return void
     */
    public function testFailedSaveRestoresVersioning(bool $contentFixture, bool $throw): void
    {
        $fixture = $this->createFixture($contentFixture);
        $entry = $this->createEntry();
        $section = $entry->getSection();
        $originalVersioning = $section->enableVersioning;
        $section->enableVersioning = true;
        $exception = new RuntimeException('Fixture save failed');
        /**
         * @param ModelEvent $event
         * @return void
         */
        $handler = static function(ModelEvent $event) use ($throw, $exception, $section): void {
            self::assertFalse($section->enableVersioning);
            if ($throw) {
                throw $exception;
            }
            $event->isValid = false;
        };
        $entry->on(Element::EVENT_BEFORE_SAVE, $handler);

        try {
            try {
                self::assertFalse($this->invokeMethod($fixture, 'saveElement', [$entry]));
                self::assertFalse($throw, 'The save should have thrown.');
            } catch (RuntimeException $caught) {
                self::assertSame($exception, $caught);
            }

            self::assertTrue($section->enableVersioning);
            $entry->off(Element::EVENT_BEFORE_SAVE, $handler);
            self::assertTrue(Craft::$app->getElements()->saveElement($entry));
            self::assertTrue($this->hasRevisions($entry));
        } finally {
            $entry->off(Element::EVENT_BEFORE_SAVE, $handler);
            $section->enableVersioning = $originalVersioning;
        }
    }

    /**
     * @return array<string, array{bool, bool}>
     */
    public static function failedSaveProvider(): array
    {
        return [
            'entry canceled' => [false, false],
            'entry exception' => [false, true],
            'content canceled' => [true, false],
            'content exception' => [true, true],
        ];
    }

    /**
     * @return void
     */
    public function testNestedMatrixRevisionsAreSuppressed(): void
    {
        $template = Entry::find()->title('Matrix with relational field')->one();
        self::assertNotNull($template);
        $entry = new Entry([
            'sectionId' => $template->sectionId,
            'typeId' => $template->typeId,
            'fieldLayoutId' => $template->fieldLayoutId,
            'title' => 'Fixture with versioned Matrix entries',
            'authorId' => 1,
        ]);
        $field = $entry->getFieldLayout()->getFieldByHandle('matrixSecond');
        self::assertInstanceOf(Matrix::class, $field);
        $originalVersioning = $field->enableVersioning;
        $field->enableVersioning = true;

        try {
            $entry->setFieldValue('matrixSecond', [
                'new1' => [
                    'type' => 'matrixLayout2',
                    'fields' => ['secondSubfield' => 'Nested fixture content'],
                ],
            ]);
            self::assertTrue($this->invokeMethod($this->createFixture(false), 'saveElement', [$entry]));
            $nested = $entry->getFieldValue('matrixSecond')->one();
            self::assertInstanceOf(Entry::class, $nested);
            self::assertFalse($this->hasRevisions($nested));
            self::assertTrue($field->enableVersioning);
            $nestedField = $nested->getField();
            self::assertInstanceOf(Matrix::class, $nestedField);
            self::assertTrue($nestedField->enableVersioning);

            $nested->setFieldValue('secondSubfield', 'Saved outside the fixture');
            self::assertTrue(Craft::$app->getElements()->saveElement($nested));
            self::assertTrue($this->hasRevisions($nested));
        } finally {
            $field->enableVersioning = $originalVersioning;
        }
    }

    /**
     * @param bool $contentFixture Whether to create a content fixture
     * @return EntryFixture|BaseContentFixture
     */
    private function createFixture(bool $contentFixture): EntryFixture|BaseContentFixture
    {
        if ($contentFixture) {
            return new class(['elementType' => Entry::class]) extends BaseContentFixture {
            };
        }

        return new class() extends EntryFixture {
        };
    }

    /**
     * @return Entry
     */
    private function createEntry(): Entry
    {
        return new Entry([
            'sectionId' => 1004,
            'typeId' => 1004,
            'title' => 'Fixture revision settings',
            'authorId' => 1,
        ]);
    }

    /**
     * @param Entry $entry The canonical entry
     * @return bool Whether the entry has any saved revisions
     */
    private function hasRevisions(Entry $entry): bool
    {
        return Entry::find()->revisionOf($entry)->site('*')->status(null)->exists();
    }
}
