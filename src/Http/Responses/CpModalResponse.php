<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use Craft;
use craft\web\assets\htmx\HtmxAsset;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Support\Facades\DeltaRegistry;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Traits\Conditionable;

use function CraftCms\Cms\template;

final class CpModalResponse implements Responsable
{
    use Conditionable;

    /**
     * @var callable|null Callable that will be called before other properties are added to the modal.
     *
     * @see prepareModal()
     */
    public $prepareModal;

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
     *
     * @see action()
     */
    public ?string $action = null;

    /**
     * @var string|null The submit button label.
     *
     * @see submitButtonLabel()
     */
    public ?string $submitButtonLabel = null;

    /**
     * @var string|callable|null The content HTML.
     *
     * @see contentHtml()
     * @see contentTemplate()
     */
    public $contentHtml;

    /**
     * @var string|callable|null The errors summary HTML (DEV-212).
     *
     * @see errorSummary()
     * @see errorSummaryTemplate()
     */
    public $errorSummary;

    /**
     * Sets a callable that will be called before other properties are added to the modal.
     */
    public function prepareModal(?callable $value): self
    {
        $this->prepareModal = $value;

        return $this;
    }

    /**
     * Sets custom attributes that should be added to the `<form>` tag.
     *
     * See [[\yii\helpers\BaseHtml::renderTagAttributes()]] for supported attribute syntaxes.
     */
    public function formAttributes(array $value): self
    {
        $this->formAttributes = $value;

        return $this;
    }

    /**
     * Sets the form action.
     */
    public function action(?string $value): self
    {
        $this->action = $value;

        return $this;
    }

    /**
     * Sets the submit button label.
     */
    public function submitButtonLabel(?string $value): self
    {
        $this->submitButtonLabel = $value;

        return $this;
    }

    /**
     * Sets the content HTML.
     */
    public function contentHtml(callable|string|null $value): self
    {
        $this->contentHtml = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the content HTML.
     */
    public function contentTemplate(string $template, array $variables = []): self
    {
        return $this->contentHtml(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp)
        );
    }

    /**
     * Sets the errors summary HTML.
     */
    public function errorSummary(callable|string|null $value): self
    {
        $this->errorSummary = $value;

        return $this;
    }

    /**
     * Sets a template that should be used to render the errors summary HTML.
     */
    public function errorSummaryTemplate(string $template, array $variables = []): self
    {
        return $this->errorSummary(
            fn () => template($template, $variables, templateMode: TemplateMode::Cp)
        );
    }

    public function toResponse($request): JsonResponse
    {
        Craft::$app->getView()->registerAssetBundle(HtmxAsset::class);

        $namespace = Str::random(10);

        if ($this->prepareModal) {
            $containerId = $request->header('X-Craft-Container-Id');

            abort_unless((bool) $containerId, 400, 'Request missing the X-Craft-Container-Id header.');

            InputNamespace::set($namespace);
            call_user_func($this->prepareModal, $this, $containerId);
            InputNamespace::set(null);
        }

        $content = InputNamespace::namespaceInputs(function () {
            $components = [];
            if ($this->contentHtml) {
                $components[] = is_callable($this->contentHtml) ? call_user_func($this->contentHtml) : $this->contentHtml;
            }
            if ($this->action) {
                $components[] = Html::actionInput($this->action, [
                    'class' => 'action-input',
                ]);
            }

            return implode("\n", $components);
        }, $namespace);

        $errorSummary = $this->errorSummary ? InputNamespace::namespaceInputs($this->errorSummary, $namespace) : null;

        return new JsonResponse([
            'namespace' => $namespace,
            'formAttributes' => $this->formAttributes,
            'action' => $this->action,
            'submitButtonLabel' => $this->submitButtonLabel,
            'content' => $content,
            'errorSummary' => $errorSummary,
            'headHtml' => AssetRegistry::headHtml(),
            'bodyHtml' => AssetRegistry::bodyHtml(),
            'deltaNames' => DeltaRegistry::getNames(),
            'initialDeltaValues' => DeltaRegistry::getInitialValues(),
        ]);
    }
}
