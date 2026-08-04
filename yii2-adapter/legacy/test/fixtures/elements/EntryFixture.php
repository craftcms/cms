<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;

/**
 * Class EntryFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
abstract class EntryFixture extends BaseElementFixture
{
    /**
     * @var array
     */
    public array $sectionIds = [];

    /**
     * @var array
     */
    public array $typeIds = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        foreach (Sections::getAllSections() as $section) {
            $this->sectionIds[$section->handle] = $section->id;
            $this->typeIds[$section->handle] = [];

            foreach (EntryTypes::getEntryTypesBySectionId($section->id) as $type) {
                $this->typeIds[$section->handle][$type->handle] = $type->id;
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new Entry();
    }
}
