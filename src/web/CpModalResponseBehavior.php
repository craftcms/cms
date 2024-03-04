<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\Html;
use yii\base\Behavior;

/**
 * Control panel modal response behavior.
 *
 * @property Response $owner
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class CpModalResponseBehavior extends Behavior
{
    public const NAME = 'cp-modal';

    /**
     * @var callable|null Callable that will be called before other properties are added to the modal.
     * @see prepareModal()
     */
    public $prepareModal = null;

    /**
     * @var array Custom attributes to add to the `<form>` tag.
     *
     * See [[\yii\helpers\BaseHtml::renderTagAttributes()]] for supported attribute syntaxes.
     *
     * @see formAttributes()
     */
    public array $formAttributes = [];

    /**
     * @var string|null The form action.
     * @see action()
     */
    public ?string $action = null;

    /**
     * @var string|null The submit button label.
     * @see submitButtonLabel()
     */
    public ?string $submitButtonLabel = null;

    /**
     * @var string|callable|null The content HTML.
     * @see contentHtml()
     * @see contentTemplate()
     */
    public $contentHtml = null;

    /**
     * @var string|callable|null The errors summary HTML (DEV-212).
     * @see errorSummary()
     * @see errorSummaryTemplate()
     */
    public $errorSummary = null;

    /**
     * Sets a callable that will be called before other properties are added to the modal.
     *
     * @param callable|null $value
     * @return Response
     */
    public function prepareModal(?callable $value): Response
    {
        $this->prepareModal = $value;
        return $this->owner;
    }

    /**
     * Sets custom attributes that should be added to the `<form>` tag.
     *
     * See [[\yii\helpers\BaseHtml::renderTagAttributes()]] for supported attribute syntaxes.
     *
     * @param array $value
     * @return Response
     */
    public function formAttributes(array $value): Response
    {
        $this->formAttributes = $value;
        return $this->owner;
    }

    /**
     * Sets the form action.
     *
     * @param string|null $value
     * @return Response
     */
    public function action(?string $value): Response
    {
        $this->action = $value;
        return $this->owner;
    }

    /**
     * Sets the submit button label.
     *
     * @param string|null $value
     * @return Response
     */
    public function submitButtonLabel(?string $value): Response
    {
        $this->submitButtonLabel = $value;
        return $this->owner;
    }

    /**
     * Sets the content HTML.
     *
     * @param callable|string|null $value
     * @return Response

     */
    public function contentHtml(callable|string|null $value): Response
    {
        $this->contentHtml = $value;
        return $this->owner;
    }

    /**
     * Sets a template that should be used to render the content HTML.
     *
     * @param string $template
     * @param array $variables
     * @return Response
     */
    public function contentTemplate(string $template, array $variables = []): Response
    {
        return $this->contentHtml(
            fn() => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP)
        );
    }

    /**
     * Sets the errors summary HTML.
     *
     * @param callable|string|null $value
     * @return Response
     */
    public function errorSummary(callable|string|null $value): Response
    {
        $this->errorSummary = $value;
        return $this->owner;
    }

    /**
     * Sets a template that should be used to render the errors summary HTML.
     *
     * @param string $template
     * @param array $variables
     * @return Response
     */
    public function errorSummaryTemplate(string $template, array $variables = []): Response
    {
        return $this->errorSummary(
            fn() => Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_CP)
        );
    }
}
