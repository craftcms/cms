<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\ArrayHelper;
use craft\models\Section;

/**
 * Class Sections variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class Sections
{
    /**
     * Returns all sections.
     *
     * @param string|null $indexBy
     * @return array
     */
    public function getAllSections(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.sections.getAllSections()', '`craft.sections.getAllSections()` has been deprecated. Use `craft.app.sections.allSections` instead.');

        $sections = Craft::$app->getSections()->getAllSections();

        return $indexBy ? ArrayHelper::index($sections, $indexBy) : $sections;
    }

    /**
     * Returns all editable sections.
     *
     * @param string|null $indexBy
     * @return array
     */
    public function getEditableSections(string $indexBy = null): array
    {
        Craft::$app->getDeprecator()->log('craft.sections.getEditableSections()', '`craft.sections.getEditableSections()` has been deprecated. Use `craft.app.sections.editableSections` instead.');

        $sections = Craft::$app->getSections()->getEditableSections();

        return $indexBy ? ArrayHelper::index($sections, $indexBy) : $sections;
    }

    /**
     * Gets the total number of sections.
     *
     * @return int
     */
    public function getTotalSections(): int
    {
        Craft::$app->getDeprecator()->log('craft.sections.getTotalSections()', '`craft.sections.getTotalSections()` has been deprecated. Use `craft.app.sections.totalSections` instead.');

        return Craft::$app->getSections()->getTotalSections();
    }

    /**
     * Gets the total number of sections that are editable by the current user.
     *
     * @return int
     */
    public function getTotalEditableSections(): int
    {
        Craft::$app->getDeprecator()->log('craft.sections.getTotalEditableSections()', '`craft.sections.getTotalEditableSections()` has been deprecated. Use `craft.app.sections.totalEditableSections` instead.');

        return Craft::$app->getSections()->getTotalEditableSections();
    }

    /**
     * Returns a section by its ID.
     *
     * @param int $sectionId
     * @return Section|null
     */
    public function getSectionById(int $sectionId)
    {
        Craft::$app->getDeprecator()->log('craft.sections.getSectionById()', '`craft.sections.getSectionById()` has been deprecated. Use `craft.app.sections.getSectionById()` instead.');

        return Craft::$app->getSections()->getSectionById($sectionId);
    }

    /**
     * Returns a section by its handle.
     *
     * @param string $handle
     * @return Section|null
     */
    public function getSectionByHandle(string $handle)
    {
        Craft::$app->getDeprecator()->log('craft.sections.getSectionByHandle()', '`craft.sections.getSectionByHandle()` has been deprecated. Use `craft.app.sections.getSectionByHandle()` instead.');

        return Craft::$app->getSections()->getSectionByHandle($handle);
    }
}
