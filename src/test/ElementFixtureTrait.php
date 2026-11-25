<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\fields\Matrix;
use craft\models\Section;
use craft\services\Elements;

/**
 * Provides element saving behavior for fixtures.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
trait ElementFixtureTrait
{
    /**
     * @var bool Whether fixture saves should create revisions when versioning is enabled.
     * This also applies to nested entries saved by the fixture.
     */
    public bool $createRevisions = false;

    /**
     * Saves an element.
     *
     * @param ElementInterface $element The element to be saved
     * @return bool Whether the save was successful
     */
    protected function saveElement(ElementInterface $element): bool
    {
        $elements = Craft::$app->getElements();
        if ($this->createRevisions) {
            return $elements->saveElement($element, true, true, false);
        }

        /** @var array<int, Section|Matrix> $versionedContainers */
        $versionedContainers = [];

        /**
         * @param ElementEvent $event
         * @return void
         */
        $beforeSave = static function(ElementEvent $event) use (&$versionedContainers): void {
            if (!$event->element instanceof Entry) {
                return;
            }

            $container = $event->element->getSection() ?? $event->element->getField();
            if (($container instanceof Section || $container instanceof Matrix) && $container->enableVersioning) {
                $versionedContainers[spl_object_id($container)] = $container;
                $container->enableVersioning = false;
            }
        };

        // Nested entries can have versioning enabled independently of their owners.
        $elements->on(Elements::EVENT_BEFORE_SAVE_ELEMENT, $beforeSave);
        try {
            return $elements->saveElement($element, true, true, false);
        } finally {
            $elements->off(Elements::EVENT_BEFORE_SAVE_ELEMENT, $beforeSave);
            foreach ($versionedContainers as $container) {
                $container->enableVersioning = true;
            }
        }
    }
}
