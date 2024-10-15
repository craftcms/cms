<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\filters;

use Craft;
use craft\base\UtilityInterface;
use craft\web\Controller;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

/**
 * Filter for ensuring the user should be able to access the configured utility.
 *
 * @property Controller $owner
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.13.0
 */
class UtilityAccess extends ActionFilter
{
    /**
     * @var string The utility class
     * @phpstan-var class-string<UtilityInterface>
     */
    public string $utility;

    /**
     * @var callable|null A PHP callable that determines when this filter should be applied.
     */
    public mixed $when = null;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (isset($this->when) && !call_user_func($this->when, $action)) {
            return true;
        }

        if (!Craft::$app->getUtilities()->checkAuthorization($this->utility)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        return true;
    }
}
