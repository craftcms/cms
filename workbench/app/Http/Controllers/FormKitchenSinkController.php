<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;
use CraftCms\Cms\Cp\Components\Select;
use CraftCms\Cms\Form\Controls\Address as AddressControl;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Workbench\App\Forms\FormKitchenSink;

class FormKitchenSinkController
{
    public function __construct(
        private readonly FormKitchenSink $kitchenSink,
        private readonly FormHtmlRenderer $htmlRenderer,
        private readonly Addresses $addresses,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect(Url::cpUrl('workbench/forms/controls/address/vue'));
    }

    public function component(string $type, string $component): RedirectResponse
    {
        return redirect(Url::cpUrl("workbench/forms/{$type}/{$component}/vue"));
    }

    public function show(Request $request, string $type, string $component, string $renderer): CpScreenResponse
    {
        $countryCode = $this->countryCode($request, $type, $component);
        $stories = $this->kitchenSink->stories($type, $component, $countryCode);

        abort_if($stories === null, 404);

        $payload = array_first($stories);
        $response = $this->response($type, $component, $renderer, $payload, $countryCode);

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
        ?string $countryCode,
    ): CpScreenResponse {
        $label = Str::headline(class_basename(FormKitchenSink::component($type, $component)));
        $url = "workbench/forms/{$type}/{$component}";

        $response = new CpScreenResponse()
            ->title("{$label} ".Str::singular($type))
            ->addCrumb('Kitchen Sink', 'workbench/forms')
            ->additionalButtonsHtml($this->countryPicker($url, $renderer, $countryCode).ButtonGroup::make()
                ->buttons([
                    Button::make()
                        ->label('Vue')
                        ->href(Url::cpUrl("{$url}/vue", $countryCode === null ? null : ['country' => $countryCode]))
                        ->active($renderer === 'vue'),
                    Button::make()
                        ->label('HTML')
                        ->href(Url::cpUrl("{$url}/html", $countryCode === null ? null : ['country' => $countryCode]))
                        ->active($renderer === 'html'),
                ])
                ->toHtml());

        return $renderer === 'html'
            ? $response->tabs($this->htmlRenderer->tabMenu($payload))
            : $response;
    }

    private function countryCode(Request $request, string $type, string $component): ?string
    {
        if (FormKitchenSink::component($type, $component) !== AddressControl::class) {
            return null;
        }

        $countries = $this->addresses->getCountryList(app()->getLocale());
        $countryCode = $request->string('country', 'BE')->value();

        abort_unless(isset($countries[$countryCode]), 404);

        return $countryCode;
    }

    private function countryPicker(string $url, string $renderer, ?string $countryCode): string
    {
        if ($countryCode === null) {
            return '';
        }

        $options = collect($this->addresses->getCountryList(app()->getLocale()))
            ->map(fn (string $label, string $value): array => compact('label', 'value'))
            ->values()
            ->all();

        return Html::tag('form',
            Select::make()
                ->name('country')
                ->value($countryCode)
                ->options($options)
                ->label('Country')
                ->labelSrOnly()
                ->small()
                ->toHtml().
            Button::make()
                ->label('Apply')
                ->type('submit')
                ->toHtml(),
            [
                'action' => Url::cpUrl("{$url}/{$renderer}"),
                'method' => 'get',
                'class' => ['flex', 'flex-nowrap'],
            ],
        );
    }
}
