<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\StringHelper;

/**
 * ProjectConfig represents the Project Configuration utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ProjectConfig extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Project Config');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'project-config';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/arrow-up.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $service = Craft::$app->getProjectConfig();
        $view = Craft::$app->getView();

        return $view->renderTemplate('_components/utilities/ProjectConfig', [
            'isUpdatePending' => $service->isUpdatePending()
        ]);
    }

}
