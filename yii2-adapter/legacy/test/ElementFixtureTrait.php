<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Event;

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
     * @param  ElementInterface  $element  The element to be saved
     * @return bool Whether the save was successful
     */
    protected function saveElement(ElementInterface $element): bool
    {
        if ($this->createRevisions) {
            return Elements::saveElement($element, true, true, false);
        }

        /** @var array<int, Section|Matrix> $versionedContainers */
        $versionedContainers = [];
        $active = true;

        // Nested entries can have versioning enabled independently of their owners. The listener stays
        // registered (Laravel has no per-closure `off()`), but `$active` makes it inert once this save
        // finishes.
        Event::listen(ElementSaving::class, static function(ElementSaving $event) use (&$active, &$versionedContainers): void {
            if (!$active || !$event->element instanceof Entry) {
                return;
            }

            $container = $event->element->getSection() ?? $event->element->getField();

            if (($container instanceof Section || $container instanceof Matrix) && $container->enableVersioning) {
                $versionedContainers[spl_object_id($container)] = $container;
                $container->enableVersioning = false;
            }
        });

        try {
            return Elements::saveElement($element, true, true, false);
        } finally {
            $active = false;

            foreach ($versionedContainers as $container) {
                $container->enableVersioning = true;
            }
        }
    }
}
