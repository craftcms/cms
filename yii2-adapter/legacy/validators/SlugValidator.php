<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use craft\base\ElementInterface;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use yii\validators\Validator;
use function CraftCms\Cms\t;

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
            $this->limitAutoSlugsToAscii = Cms::config()->limitAutoSlugsToAscii;
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
        if ($isDraft && !in_array($model->getScenario(), [Element::SCENARIO_LIVE, Element::SCENARIO_DEFAULT])) {
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
                $this->addError($model, $attribute, t('{attribute} is invalid.'));
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
            return [t('{attribute} cannot be blank.'), []];
        }

        $slug = ElementHelper::normalizeSlug($value);

        if ($slug !== $value) {
            return [t('{attribute} is invalid.'), []];
        }

        return null;
    }
}
