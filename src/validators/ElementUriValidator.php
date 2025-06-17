<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\errors\OperationAbortedException;
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
    public function init(): void
    {
        parent::init();

        $this->skipOnEmpty = false;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException if $attribute is not 'uri'
     */
    public function validateAttribute($model, $attribute): void
    {
        if ($attribute !== 'uri' || !$model instanceof ElementInterface) {
            throw new InvalidConfigException('Invalid use of ElementUriValidator');
        }

        // Ignore revisions
        if ($model->getIsRevision()) {
            return;
        }

        // Ignore published drafts if the scenario isn't "live",
        // or if the canonical element is enabled and the URI hasn't changed on the draft
        if ($model->getIsDraft() && !$model->getIsUnpublishedDraft()) {
            if ($model->getScenario() !== Element::SCENARIO_LIVE) {
                return;
            }

            $canonical = $model->getCanonical();
            if (
                $canonical !== $model &&
                $model->uri === $canonical->uri &&
                $canonical->enabled &&
                $canonical->getEnabledForSite()
            ) {
                return;
            }
        }

        try {
            Craft::$app->getElements()->setElementUri($model);
        } catch (OperationAbortedException) {
            // Not a big deal if the element isn't enabled yet
            if (
                $model->enabled &&
                $model->getEnabledForSite() &&
                (!$model->getIsUnpublishedDraft() || $model->getScenario() === Element::SCENARIO_LIVE)
            ) {
                $this->addError($model, $attribute, Craft::t('app', 'Could not generate a unique URI based on the URI format.'));
                return;
            }
        }

        if (!$this->isEmpty($model->uri)) {
            parent::validateAttribute($model, $attribute);
        }
    }
}
