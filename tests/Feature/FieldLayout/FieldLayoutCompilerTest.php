<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Field\Models\Field as FieldModel;
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
use CraftCms\Cms\Form\Controls\Missing as MissingControl;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\Nodes\Callout;
use CraftCms\Cms\Form\Nodes\Heading as HeadingNode;
use CraftCms\Cms\Form\Nodes\LineBreak as LineBreakNode;
use CraftCms\Cms\Form\Nodes\MarkdownContent;
use CraftCms\Cms\Form\Nodes\Missing as MissingNode;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Symfony\Component\DomCrawler\Crawler;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;

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
                'content' => "<script>alert(1)</script>\n\n**Editorial note**",
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
        ->and($content->text())->not->toContain('alert(1)')
        ->and($content->filter('strong')->text())->toBe('Editorial note')
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
        ->and($layoutModel->fresh()->config)->toEqual($config);
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
        ->and($crawler->filter('[data-form-node="heading"] h2')->text())->toBe('Details')
        ->and($crawler->filter('hr[data-form-node="separator"]'))->toHaveCount(1)
        ->and($crawler->filter('.line-break[data-form-node="break"]'))->toHaveCount(1)
        ->and($crawler->filter('craft-callout[data-form-node="warning"]')->text())->toContain('Careful');
});

it('preserves missing persisted form providers without submitting their values', function () {
    $missingNodeType = 'Acme\\Forms\\MissingLayoutElement';
    $missingControlType = 'Acme\\Forms\\MissingField';
    actingAs(User::find()->one());
    app()->instance(Plugins::class, Mockery::mock(Plugins::class, function ($plugins) use ($missingNodeType, $missingControlType) {
        $plugins->shouldReceive('getPluginHandleByClass')->andReturn(null)->byDefault();
        $plugins->shouldReceive('getPluginHandleByClass')->with($missingNodeType)->andReturnUsing(
            fn () => class_exists($missingNodeType, false) ? null : 'missing-node-plugin',
        );
        $plugins->shouldReceive('getPluginHandleByClass')->with($missingControlType)->andReturnUsing(
            fn () => class_exists($missingControlType, false) ? null : 'missing-control-plugin',
        );
        $plugins->shouldReceive('getPluginInfo')->with('missing-node-plugin')->andReturn([
            'isInstalled' => true,
            'name' => 'Missing Node Plugin',
        ]);
        $plugins->shouldReceive('getPluginInfo')->with('missing-control-plugin')->andReturn([
            'isInstalled' => false,
            'name' => 'Missing Control Plugin',
        ]);
        $plugins->shouldReceive('getPluginIconSvg')->andReturn('<svg></svg>');
    }));
    $layout = FieldLayout::make(CraftCms\Cms\Entry\Elements\Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(
            new Markdown(['uid' => 'missing-node', 'content' => 'Temporary']),
        ));
    $layout->getTabs()[0]->uid = 'tab-content';
    $config = $layout->getConfig();
    $config['tabs'][0]['elements'][0] = $missingNodeConfig = [
        'type' => $missingNodeType,
        'uid' => 'missing-node',
        'width' => 50,
        'content' => 'Restored node content',
        'displayInPane' => false,
    ];
    $layout = FieldLayout::createFromConfig($config);
    $field = app(Fields::class)->createField([
        'type' => $missingControlType,
        'name' => 'Unavailable field',
        'handle' => 'unavailable',
        'uid' => 'missing-field',
        'settings' => ['placeholder' => 'Restored placeholder'],
    ]);
    $missingControl = CustomField::make($field);
    $missingControl->uid = 'missing-control';
    $layout->getTabs()[0]->add($missingControl);

    $payload = app(FieldLayoutCompiler::class)->compile(
        $layout,
        context: new FormContext(values: ['fields' => ['unavailable' => 'Original content']]),
    );
    $children = $payload->nodes[0]->children;
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));
    $twigPlaceholder = new Crawler($field->getPlaceholderHtml());

    expect($field)->toBeInstanceOf(MissingField::class)
        ->and($children[0]->type)->toBe(MissingNode::class)
        ->and($children[0]->props['provider'])->toBe($missingNodeType)
        ->and($children[0]->props['pluginName'])->toBe('Missing Node Plugin')
        ->and($children[0]->props['action'])->toBe([
            'label' => 'Enable',
            'url' => cp_url('settings/plugins/missing-node-plugin/enable'),
            'method' => 'post',
        ])
        ->and($children[1]->control?->type)->toBe(MissingControl::class)
        ->and($children[1]->control?->props['provider'])->toBe($missingControlType)
        ->and($children[1]->control?->props['pluginName'])->toBe('Missing Control Plugin')
        ->and($children[1]->control?->props['action'])->toBe([
            'label' => 'Install',
            'url' => cp_url('settings/plugins/missing-control-plugin/install'),
            'method' => 'post',
        ])
        ->and($crawler->filter('input, select, textarea'))->toHaveCount(0)
        ->and($crawler->filter('craft-missing-component')->eq(0)->attr('error'))->toContain($missingNodeType)
        ->and($crawler->filter('craft-missing-component')->eq(1)->attr('error'))->toContain($missingControlType)
        ->and($crawler->filter('button[formaction="'.cp_url('settings/plugins/missing-node-plugin/enable').'"]'))->toHaveCount(1)
        ->and($crawler->filter('button[formaction="'.cp_url('settings/plugins/missing-control-plugin/install').'"]'))->toHaveCount(1)
        ->and($crawler->filter('button[form]'))->toHaveCount(0)
        ->and($twigPlaceholder->filter('craft-missing-component')->attr('error'))->toContain($missingControlType)
        ->and($twigPlaceholder->filter('button[formaction="'.cp_url('settings/plugins/missing-control-plugin/install').'"]'))->toHaveCount(1)
        ->and($layout->getConfig()['tabs'][0]['elements'][0])->toBe($missingNodeConfig)
        ->and($fieldConfig = ProjectConfigHelper::unpackAssociativeArrays(app(Fields::class)->createFieldConfig($field)))->toMatchArray([
            'type' => $missingControlType,
            'settings' => ['placeholder' => 'Restored placeholder'],
        ]);

    class_alias(Markdown::class, $missingNodeType);
    class_alias(PlainText::class, $missingControlType);
    $recoveredLayout = FieldLayout::createFromConfig($config);
    $recoveredField = app(Fields::class)->createField($fieldConfig + ['uid' => 'missing-field']);
    $recoveredControl = CustomField::make($recoveredField);
    $recoveredControl->uid = 'missing-control';
    $recoveredLayout->getTabs()[0]->add($recoveredControl);
    $recovered = app(FieldLayoutCompiler::class)->compile(
        $recoveredLayout,
        context: new FormContext(values: ['fields' => ['unavailable' => 'Original content']]),
    );
    $recoveredCrawler = new Crawler(app(FormHtmlRenderer::class)->render($recovered));

    expect($recovered->nodes[0]->children[0]->type)->toBe(MarkdownContent::class)
        ->and($recovered->nodes[0]->children[1]->control?->component)->toBe('craft:text')
        ->and($recovered->values)->toBe(['fields' => ['unavailable' => 'Original content']])
        ->and($recoveredCrawler->filter('input[name="fields[unavailable]"]'))->toHaveCount(1);
});

it('carries per-field change-tracking status into the payload when compiling a draft', function () {
    actingAs(User::findOne());

    $field = FieldModel::factory()->create([
        'handle' => 'body',
        'type' => PlainText::class,
    ]);

    // Full-width uids on purpose: change tracking stores `layoutElementUid` in
    // a `char(36)` column, and Postgres blank-pads a shorter value back out to
    // 36 characters on read, so it stops matching the layout element it names.
    // Real uids are UUIDs, so fixtures have to be the same width to be honest.
    $entryModel = Entry::factory()
        ->withFieldLayout(FieldLayoutModel::factory()->withContentTab([
            new EntryTitleField(['uid' => '5c2e4c8a-9f3d-4f1a-9f5a-1b1e6f0d7a01']),
            new CustomField(config: [
                'uid' => '5c2e4c8a-9f3d-4f1a-9f5a-1b1e6f0d7a02',
                'fieldUid' => $field->uid,
            ]),
        ]))
        ->create();

    $canonical = entryQuery()->id($entryModel->id)->one();
    $canonical->title = 'Canonical title';
    $canonical->setFieldValue('body', 'Canonical body');
    Elements::saveElement($canonical);

    $draft = app(Drafts::class)->createDraft($canonical, User::findOne()->id);
    $draft->title = 'Draft title';
    $draft->setDirtyAttributes(['title']);
    $draft->setFieldValue('body', 'Draft body');
    $draft->setDirtyFields(['body']);
    Elements::saveElement($draft);

    $draft = entryQuery()->id($draft->id)->drafts()->one();

    $canonicalPayload = app(FieldLayoutCompiler::class)->compile(
        $canonical->getFieldLayout(),
        $canonical,
    );
    $payload = app(FieldLayoutCompiler::class)->compile($draft->getFieldLayout(), $draft);

    $props = collect($payload->nodes[0]->children)
        ->keyBy(fn ($node) => implode('.', $node->control?->path ?? []))
        ->map(fn ($node) => $node->props);

    expect($draft->getModifiedAttributes())->toContain('title')
        ->and($draft->getModifiedFields())->toContain('body')
        ->and($props['title']['status'])->toBe('modified')
        ->and($props['title']['statusLabel'])->toBe('This field has been modified.')
        ->and($props['fields.body']['status'])->toBe('modified')
        ->and($props['fields.body']['statusLabel'])->toBe('This field has been modified.');

    // The canonical element never reports a status.
    expect(collect($canonicalPayload->nodes[0]->children)->pluck('props')->pluck('status')->filter())
        ->toBeEmpty();

    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));

    expect($crawler->filter('craft-field[status="modified"]'))->toHaveCount(2)
        ->and($crawler->filter('craft-field[status="modified"]')->eq(0)->attr('status-label'))
        ->toBe('This field has been modified.');
});

it('reports outdated status for fields changed on the canonical element since the draft was created', function () {
    actingAs(User::findOne());

    $field = FieldModel::factory()->create([
        'handle' => 'body',
        'type' => PlainText::class,
    ]);

    // Full-width uids on purpose: change tracking stores `layoutElementUid` in
    // a `char(36)` column, and Postgres blank-pads a shorter value back out to
    // 36 characters on read, so it stops matching the layout element it names.
    // Real uids are UUIDs, so fixtures have to be the same width to be honest.
    $entryModel = Entry::factory()
        ->withFieldLayout(FieldLayoutModel::factory()->withContentTab([
            new EntryTitleField(['uid' => '5c2e4c8a-9f3d-4f1a-9f5a-1b1e6f0d7a01']),
            new CustomField(config: [
                'uid' => '5c2e4c8a-9f3d-4f1a-9f5a-1b1e6f0d7a02',
                'fieldUid' => $field->uid,
            ]),
        ]))
        ->create();

    $canonical = entryQuery()->id($entryModel->id)->one();
    $canonical->title = 'Canonical title';
    $canonical->setFieldValue('body', 'Canonical body');
    Elements::saveElement($canonical);

    $draft = app(Drafts::class)->createDraft($canonical, User::findOne()->id);

    // The canonical element moves on after the draft was created.
    $canonical = entryQuery()->id($entryModel->id)->one();
    $canonical->title = 'Newer canonical title';
    $canonical->setDirtyAttributes(['title']);
    $canonical->setFieldValue('body', 'Newer canonical body');
    $canonical->setDirtyFields(['body']);
    Elements::saveElement($canonical);

    $draft = entryQuery()->id($draft->id)->drafts()->one();

    $payload = app(FieldLayoutCompiler::class)->compile($draft->getFieldLayout(), $draft);
    $props = collect($payload->nodes[0]->children)
        ->keyBy(fn ($node) => implode('.', $node->control?->path ?? []))
        ->map(fn ($node) => $node->props);

    expect($draft->getOutdatedAttributes())->toContain('title')
        ->and($draft->getOutdatedFields())->toContain('body')
        ->and($props['title']['status'])->toBe('outdated')
        ->and($props['title']['statusLabel'])->toBe('This field was updated in the Current revision.')
        ->and($props['fields.body']['status'])->toBe('outdated');
});
