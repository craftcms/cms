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
    public ?string $sourceAttribute = 'title';

    /**
     * @var bool|null Whether auto-generated slugs should be limited to ASCII characters. Defaults to the `limitAutoSlugsToAscii` config setting if left null.
     */
    public ?bool $limitAutoSlugsToAscii = null;

    /**
     * @var string|null The language to pull ASCII character mappings for, if [[limitAutoSlugsToAscii]] is enabled.
     * @since 3.1.9
     */
    public ?string $language = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (isset($this->sourceAttribute)) {
            $this->skipOnEmpty = false;
        }

        if (!isset($this->limitAutoSlugsToAscii)) {
            $this->limitAutoSlugsToAscii = Craft::$app->getConfig()->getGeneral()->limitAutoSlugsToAscii;
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        $slug = $originalSlug = (string)$model->$attribute;
        $isTemp = ElementHelper::isTempSlug($slug);
        $isDraft = $model instanceof ElementInterface && $model->getIsDraft();

        // If this is a draft with a temp slug, leave it alone
        if ($isDraft) {
            if ($isTemp) {
                // Leave it alone
                return;
            }

            if ($slug === '') {
                $model->$attribute = ElementHelper::tempSlug();
                return;
            }
        }

        if (($slug === '' || $isTemp) && isset($this->sourceAttribute)) {
            // Create a new slug for them, based on the element’s title.
            $slug = ElementHelper::generateSlug((string)$model->{$this->sourceAttribute}, $this->limitAutoSlugsToAscii, $this->language);
        } else {
            // Apply normal slug rules
            $slug = ElementHelper::normalizeSlug($slug);
        }

        if ($slug !== '') {
            $model->$attribute = $slug;
        } elseif (!$isTemp) {
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
    protected function validateValue($value): ?array
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
