<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use craft\web\Controller;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utility;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

/**
 * Filter for ensuring the user should be able to access the configured utility.
 *
 * @property Controller $owner
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.5.0
 */
class UtilityAccess extends ActionFilter
{
    use ConditionalFilterTrait;

    /**
     * @var class-string<Utility> The utility class
     */
    public string $utility;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!app(Utilities::class)->checkAuthorization($this->utility)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        return true;
    }
}
