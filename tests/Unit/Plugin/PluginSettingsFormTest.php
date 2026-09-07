<?php

declare(strict_types=1);

use CraftCms\Cms\Form\Controls\ContentBlock;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Plugin\PluginSettingsForm;

it('refreshes nested settings without losing other values or replaying validation errors', function () {
    $plugin = new class(app()) extends Plugin
    {
        public string $handle = 'nested-settings';

        protected function createSettingsModel(): PluginSettings
        {
            return new class extends PluginSettings
            {
                public string $title = 'Saved title';

                public array $content = ['body' => 'Saved body'];
            };
        }

        public function settingsForm(FormContext $context = new FormContext): Form
        {
            return Form::make([
                Field::make('Title', Text::make('title')),
                Field::make('Content', ContentBlock::make('content')->form(Form::make([
                    Field::make('Body', Text::make('body')),
                ]))),
            ]);
        }
    };
    $plugin->getSettings()->errors()->add('content.body', 'Body is invalid.');
    $settingsForm = app(PluginSettingsForm::class);

    $initial = $settingsForm->render($plugin);

    expect($initial->values['settings'])->toBe([
        'title' => 'Saved title',
        'content' => ['body' => 'Saved body'],
    ])->and($initial->errors)->toBe([
        ['path' => ['settings', 'content', 'body'], 'messages' => ['Body is invalid.']],
    ]);

    $refreshed = $settingsForm->refresh($plugin, ['body' => 'Edited body'], ['settings', 'content']);

    expect($refreshed->scope)->toBe(['settings', 'content'])
        ->and($refreshed->nodes)->toHaveCount(1)
        ->and($refreshed->nodes[0]->control->path)->toBe(['settings', 'content', 'body'])
        ->and($refreshed->values['settings'])->toBe([
            'title' => 'Saved title',
            'content' => ['body' => 'Edited body'],
        ])
        ->and($refreshed->errors)->toBe([])
        ->and($plugin->getSettings()->content)->toBe(['body' => 'Edited body']);
});
