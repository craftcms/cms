<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use craft\helpers\ElementHelper;
use CraftCms\Cms\Cms;
use yii\validators\Validator;
use function CraftCms\Cms\t;

/**
 * Will validate that the given attribute is a valid URI format.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UriFormatValidator extends Validator
{
    /**
     * @var bool Whether we should ensure that "{slug}" is used within the URI format.
     */
    public bool $requireSlug = false;

    /**
     * @var bool Whether to ensure that the URI format doesn’t begin with the actionTrigger or cpTrigger.
     * @since 3.2.10
     */
    public bool $disallowTriggers = true;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute): void
    {
        $uriFormat = $model->$attribute;

        if (is_string($uriFormat)) {
            // Remove any leading or trailing slashes/spaces
            $model->$attribute = trim($uriFormat, '/ ');
        }

        parent::validateAttribute($model, $attribute);
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        if ($this->requireSlug && !ElementHelper::doesUriFormatHaveSlugTag($value)) {
            return [t('{attribute} must contain “{slug}”'), []];
        }

        if ($this->disallowTriggers) {
            $generalConfig = Cms::config();
            $firstSeg = explode('/', $value, 2)[0];

            if ($firstSeg === $generalConfig->actionTrigger) {
                return [
                    t('{attribute} cannot start with the {setting} config setting.'), [
                    'setting' => 'actionTrigger',
                ], ];
            } elseif ($generalConfig->cpTrigger && $firstSeg === $generalConfig->cpTrigger) {
                return [
                    t('{attribute} cannot start with the {setting} config setting.'), [
                    'setting' => 'cpTrigger',
                ], ];
            }
        }

        return null;
    }
}
