<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutFormResolving;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutCompiler;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\LayoutElements\Heading;
use CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule;
use CraftCms\Cms\FieldLayout\LayoutElements\LineBreak;
use CraftCms\Cms\FieldLayout\LayoutElements\Markdown;
use CraftCms\Cms\FieldLayout\LayoutElements\Tip;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\Nodes\Callout;
use CraftCms\Cms\Form\Nodes\Heading as HeadingNode;
use CraftCms\Cms\Form\Nodes\LineBreak as LineBreakNode;
use CraftCms\Cms\Form\Nodes\MarkdownContent;
use CraftCms\Cms\Form\Nodes\Separator;
use Illuminate\Support\Facades\Event;
use Symfony\Component\DomCrawler\Crawler;

function persistedEntryLayout(): FieldLayoutModel
{
    $layout = FieldLayout::make(CraftCms\Cms\Entry\Elements\Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(
            new EntryTitleField([
                'uid' => 'field-title',
                'label' => 'Headline',
                'instructions' => 'Keep it short.',
                'instructionsPosition' => 'after',
                'tip' => 'Use sentence case.',
                'warning' => 'This appears publicly.',
                'required' => true,
                'readonly' => true,
                'width' => 50,
            ]),
            new Markdown([
                'uid' => 'content-note',
                'content' => '<script>alert(1)</script> **Editorial note**',
                'displayInPane' => false,
                'width' => 50,
            ]),
        ))
        ->tab('Hidden', fn (FieldLayoutTab $tab) => $tab->add(
            new Markdown([
                'uid' => 'hidden-note',
                'content' => 'Hidden',
            ]),
        ));

    $config = $layout->getConfig();
    $config['tabs'][0]['uid'] = 'tab-content';
    $config['tabs'][1]['uid'] = 'tab-hidden';

    return FieldLayoutModel::factory()->create([
        'type' => CraftCms\Cms\Entry\Elements\Entry::class,
        'config' => $config,
    ]);
}

it('compiles persisted entry layout intent into a form payload', function () {
    $layoutModel = persistedEntryLayout();
    $entry = Entry::factory()
        ->withFieldLayout($layoutModel)
        ->createElement(['title' => 'Persisted title']);

    Event::listen(FieldLayoutComponentShowInFormResolving::class, function (FieldLayoutComponentShowInFormResolving $event) {
        if ($event->fieldLayoutComponent->uid === 'hidden-note') {
            $event->showInForm = false;
            $event->handled = true;
        }
    });

    $payload = app(FieldLayoutCompiler::class)->compile(
        $entry->getFieldLayout(),
        $entry,
        new FormContext(errors: ['title' => ['Title is invalid.']]),
    );
    $contentNode = $payload->nodes[0]->children[1];
    $content = new Crawler($contentNode->props['html']);

    expect($payload->nodes)->toHaveCount(1)
        ->and($payload->nodes[0]->uid)->toBe('tab-content')
        ->and($payload->nodes[0]->props)->toBe(['label' => 'Content'])
        ->and($payload->nodes[0]->children)->toHaveCount(2)
        ->and($payload->nodes[0]->children[0]->props)->toMatchArray([
            'label' => 'Headline',
            'instructions' => 'Keep it short.',
            'instructionsPosition' => 'after',
            'tip' => 'Use sentence case.',
            'warning' => 'This appears publicly.',
            'required' => true,
            'layoutUid' => 'field-title',
            'width' => 50,
        ])
        ->and($payload->nodes[0]->children[0]->control?->path)->toBe(['title'])
        ->and($payload->nodes[0]->children[0]->control?->mode)->toBe(ControlMode::ReadOnly)
        ->and($contentNode->type)->toBe(MarkdownContent::class)
        ->and($contentNode->uid)->toBe('content-note')
        ->and($contentNode->props)->toMatchArray([
            'displayInPane' => false,
            'width' => 50,
        ])
        ->and($content->filter('script'))->toHaveCount(0)
        ->and($content->filter('strong')->text())->toBe('Editorial note')
        ->and($content->text())->toContain('<script>alert(1)</script>')
        ->and($payload->values)->toBe(['title' => 'Persisted title'])
        ->and($payload->errors)->toBe([[
            'path' => ['title'],
            'messages' => ['Title is invalid.'],
        ]]);

    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));
    $field = $crawler->filter('craft-field[data-layout-element="field-title"]');

    expect($crawler->filter('section[data-form-tab="tab-content"]'))->toHaveCount(1)
        ->and($field)->toHaveCount(1)
        ->and($field->attr('class'))->toContain('width-50')
        ->and($field->attr('instructions-position'))->toBe('after')
        ->and($field->text())->toContain('Use sentence case.')
        ->and($field->text())->toContain('This appears publicly.')
        ->and($crawler->filter('[data-form-node="content-note"] strong')->text())->toBe('Editorial note');
});

it('allows the form-stage event to replace typed nodes without changing persisted intent', function () {
    $layoutModel = persistedEntryLayout();
    $layout = app(Fields::class)->getLayoutById($layoutModel->id);
    $config = $layoutModel->config;

    Event::listen(FieldLayoutFormResolving::class, function (FieldLayoutFormResolving $event) {
        $nodes = $event->form->nodes();
        $event->form = Form::make([
            $nodes[1],
            MarkdownContent::make('injected-note', 'Injected'),
        ]);
    });

    $payload = app(FieldLayoutCompiler::class)->compile($layout, context: new FormContext);

    expect(array_column($payload->nodes, 'uid'))->toBe(['tab-hidden', 'injected-note'])
        ->and($layoutModel->fresh()->config)->toBe($config);
});

it('compiles custom fields and shared semantic layout content', function () {
    $field = new PlainText([
        'name' => 'Body',
        'handle' => 'body',
        'uid' => 'field-body',
        'multiline' => true,
        'initialRows' => 6,
    ]);
    $customField = new class($field, ['uid' => 'layout-body', 'width' => 50]) extends CustomField
    {
        public function editable(?ElementInterface $element): bool
        {
            return false;
        }
    };
    $layout = FieldLayout::make(CraftCms\Cms\Entry\Elements\Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(
            $customField,
            new Heading(['uid' => 'heading', 'heading' => 'Details']),
            new HorizontalRule(['uid' => 'separator']),
            new LineBreak(['uid' => 'break']),
            new Tip(['uid' => 'warning', 'tip' => '**Careful**', 'style' => Tip::STYLE_WARNING]),
        ));
    $layout->getTabs()[0]->uid = 'tab-content';

    $payload = app(FieldLayoutCompiler::class)->compile($layout);
    $children = $payload->nodes[0]->children;
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($children)->toHaveCount(5)
        ->and($children[0]->control?->component)->toBe('craft:textarea')
        ->and($children[0]->control?->path)->toBe(['fields', 'body'])
        ->and($children[0]->control?->mode)->toBe(ControlMode::ReadOnly)
        ->and($children[0]->props)->toMatchArray(['layoutUid' => 'layout-body', 'width' => 50])
        ->and(array_column(array_slice($children, 1), 'type'))->toBe([
            HeadingNode::class,
            Separator::class,
            LineBreakNode::class,
            Callout::class,
        ])
        ->and($children[4]->props)->toMatchArray([
            'variant' => 'warning',
            'dismissible' => false,
        ])
        ->and($crawler->filter('h2[data-form-node="heading"]')->text())->toBe('Details')
        ->and($crawler->filter('hr[data-form-node="separator"]'))->toHaveCount(1)
        ->and($crawler->filter('.line-break[data-form-node="break"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-callout[data-form-node="warning"]')->text())->toContain('Careful');
});
