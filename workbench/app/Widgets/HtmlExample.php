<?php

declare(strict_types=1);

namespace Workbench\App\Widgets;

use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Override;

class HtmlExample extends Widget
{
    public string $message = 'Hello from a PHP HTML widget!';

    #[Override]
    public static function displayName(): string
    {
        return 'HTML Example';
    }

    #[Override]
    public function getRules(): array
    {
        return ['message' => ['required', 'string']];
    }

    #[Override]
    public function settingsForm(FormContext $context = new FormContext): Form
    {
        return Form::make([
            Field::make('Message')
                ->required()
                ->control(Text::make('message')->value($this->message)),
        ]);
    }

    #[Override]
    public function getBodyHtml(): string
    {
        $id = "html-example-{$this->id}";
        $message = htmlspecialchars($this->message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        HtmlStack::css('.html-example output { font-weight: bold; font-variant-numeric: tabular-nums; }');

        HtmlStack::js(<<<JS
            (() => {
                const container = document.getElementById('$id');
                const output = container.querySelector('output');

                container.querySelector('craft-button').addEventListener('click', () => {
                    output.value = String(Number(output.value) + 1);
                });
            })();
            JS);

        return <<<HTML
            <div id="$id" class="html-example">
                <p>$message</p>
                <p>Clicks: <output aria-live="polite">0</output></p>
                <craft-button type="button">Increment</craft-button>
            </div>
            HTML;
    }
}
