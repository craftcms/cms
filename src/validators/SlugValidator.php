<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use yii\validators\Validator;

/**
 * Class SlugValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SlugValidator extends Validator
{
    /**
     * @var string|null The source attribute that auto-generated slugs should be based on. Set to null to skip validation for blank slugs.
     */
    public $sourceAttribute = 'title';

    /**
     * @var bool|null Whether auto-generated slugs should be limited to ASCII characters. Defaults to the `limitAutoSlugsToAscii` config setting if left null.
     */
    public $limitAutoSlugsToAscii;

    /**
     * @var string|null The language to pull ASCII character mappings for, if [[limitAutoSlugsToAscii]] is enabled.
     * @since 3.1.9
     */
    public $language;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->sourceAttribute !== null) {
            $this->skipOnEmpty = false;
        }

        if ($this->limitAutoSlugsToAscii === null) {
            $this->limitAutoSlugsToAscii = Craft::$app->getConfig()->getGeneral()->limitAutoSlugsToAscii;
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $slug = $originalSlug = (string)$model->$attribute;
        $isTemp = ElementHelper::isTempSlug($slug);
        $isDraft = $model instanceof ElementInterface && $model->getIsDraft();

        // If this is a draft with a temp slug, leave it alone
        if ($isTemp && $isDraft) {
            return;
        }

        if (($slug === '' || $isTemp) && $this->sourceAttribute !== null) {
            // Create a new slug for them, based on the element's title.
            $slug = ElementHelper::generateSlug($model->{$this->sourceAttribute}, $this->limitAutoSlugsToAscii, $this->language);
        } else {
            // Apply normal slug rules
            $slug = ElementHelper::normalizeSlug($slug);
        }

        if ($slug !== '') {
            $model->$attribute = $slug;
        } else if (!$isTemp) {
            if ($originalSlug !== '') {
                $this->addError($model, $attribute, Craft::t('yii', '{attribute} is invalid.'));
            } else {
                $this->addError($model, $attribute, Craft::t('yii', '{attribute} cannot be blank.'));
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        $value = (string)$value;

        if ($value === '') {
            return [Craft::t('yii', '{attribute} cannot be blank.'), []];
        }

        $slug = ElementHelper::normalizeSlug($value);

        if ($slug !== $value) {
            return [Craft::t('yii', '{attribute} is invalid.'), []];
        }

        return null;
    }
}
