<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\validators;

use Craft;
use craft\web\View;
use yii\validators\Validator;

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
     */
    public $templateMode = View::TEMPLATE_MODE_SITE;

    /**
     * @var string user-defined error message used when the value is not a string.
     */
    public $message;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->message === null) {
            $this->message = str_replace('{template}', '{value}', Craft::t('app', 'Unable to find the template “{template}”.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateValue($value)
    {
        if (Craft::$app->getView()->resolveTemplate($value, $this->templateMode) === false) {
            return [$this->message, []];
        }

        return null;
    }
}
