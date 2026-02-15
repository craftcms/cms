<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\web\View;
use CraftCms\Cms\View\TemplateMode;
use yii\validators\Validator;
use function CraftCms\Cms\t;

/**
 * Class TemplateValidator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class TemplateValidator extends Validator
{
    /**
     * @var string The template mode to use when looking for the template
     * @phpstan-var View::TEMPLATE_MODE_SITE|View::TEMPLATE_MODE_CP
     */
    public string $templateMode = TemplateMode::Site->value;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->message)) {
            $this->message = str_replace('{template}', '{value}', t('Unable to find the template “{template}”.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value): ?array
    {
        if (Craft::$app->getView()->resolveTemplate($value, $this->templateMode) === false) {
            return [$this->message, []];
        }

        return null;
    }
}
