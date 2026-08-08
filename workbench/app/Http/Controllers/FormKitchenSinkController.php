<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Workbench\App\Forms\FormKitchenSink;

class FormKitchenSinkController
{
    public function __construct(
        private readonly FormKitchenSink $kitchenSink,
        private readonly FormHtmlRenderer $htmlRenderer,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect(Url::cpUrl('workbench/forms/controls/address/vue'));
    }

    public function component(string $type, string $component): RedirectResponse
    {
        return redirect(Url::cpUrl("workbench/forms/{$type}/{$component}/vue"));
    }

    public function show(string $type, string $component, string $renderer): CpScreenResponse
    {
        $stories = $this->kitchenSink->stories($type, $component);

        abort_if($stories === null, 404);

        $payload = array_first($stories);
        $response = $this->response($type, $component, $renderer, $payload);

        if ($renderer === 'vue') {
            return $response->inertiaPage('workbench/FormKitchenSink', ['stories' => $stories]);
        }

        return $response->contentHtml(collect($stories)
            ->map(fn (FormPayload $story, string $name): string => Html::tag('article',
                Html::tag('h2', Html::encode($name), ['class' => 'text-lg']).
                Html::tag('form', $this->htmlRenderer->render($story), ['class' => 'pane']),
                ['class' => 'mb-xl'],
            ))
            ->implode(''));
    }

    private function response(
        string $type,
        string $component,
        string $renderer,
        FormPayload $payload,
    ): CpScreenResponse {
        $label = Str::headline(class_basename(FormKitchenSink::component($type, $component)));
        $url = "workbench/forms/{$type}/{$component}";

        $response = new CpScreenResponse()
            ->title("{$label} ".Str::singular($type))
            ->addCrumb('Kitchen Sink', 'workbench/forms')
            ->additionalButtonsHtml(ButtonGroup::make()
                ->buttons([
                    Button::make()
                        ->label('Vue')
                        ->href(Url::cpUrl("{$url}/vue"))
                        ->active($renderer === 'vue'),
                    Button::make()
                        ->label('HTML')
                        ->href(Url::cpUrl("{$url}/html"))
                        ->active($renderer === 'html'),
                ])
                ->toHtml());

        return $renderer === 'html'
            ? $response->tabs($this->htmlRenderer->tabMenu($payload))
            : $response;
    }
}
