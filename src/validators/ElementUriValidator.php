<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\errors\OperationAbortedException;
use craft\helpers\ElementHelper;
use yii\base\InvalidConfigException;

/**
 * Class ElementUriValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementUriValidator extends UriValidator
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->skipOnEmpty = false;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if $attribute is not 'uri'
     */
    public function validateAttribute($model, $attribute)
    {
        if ($attribute !== 'uri') {
            throw new InvalidConfigException('Invalid use of ElementUriValidator');
        }

        // If this is a draft or revision and it already has a URI, leave it alone
        if (
            $model->uri &&
            (
                ($model->getIsDraft() && !$model->getIsUnsavedDraft()) ||
                $model->getIsRevision()
            )
        ) {
            return;
        }

        try {
            ElementHelper::setUniqueUri($model);
        } catch (OperationAbortedException $e) {
            // Not a big deal if the element isn't enabled yet
            if ($model->enabled && $model->getEnabledForSite()) {
                $this->addError($model, $attribute, Craft::t('app', 'Could not generate a unique URI based on the URI format.'));
                return;
            }
        }

        if (!$this->isEmpty($model->uri)) {
            parent::validateAttribute($model, $attribute);
        }
    }
}
