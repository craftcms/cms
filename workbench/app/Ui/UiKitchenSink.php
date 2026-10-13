<?php

declare(strict_types=1);

namespace Workbench\App\Ui;

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Markdown as MarkdownField;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Address;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Color;
use CraftCms\Cms\Ui\Controls\Combobox;
use CraftCms\Cms\Ui\Controls\ConditionBuilder;
use CraftCms\Cms\Ui\Controls\ContentBlock;
use CraftCms\Cms\Ui\Controls\Date;
use CraftCms\Cms\Ui\Controls\DateTime;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\Ui\Controls\FieldLayoutDesigner;
use CraftCms\Cms\Ui\Controls\GroupedEntryTypeManager;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\IconPicker;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Link;
use CraftCms\Cms\Ui\Controls\Markdown;
use CraftCms\Cms\Ui\Controls\Missing as MissingControl;
use CraftCms\Cms\Ui\Controls\Money;
use CraftCms\Cms\Ui\Controls\NestedElementBlocks;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\PermissionTree;
use CraftCms\Cms\Ui\Controls\Range;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Controls\Time;
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Nodes\Callout;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\Heading;
use CraftCms\Cms\Ui\Nodes\LineBreak;
use CraftCms\Cms\Ui\Nodes\MarkdownContent;
use CraftCms\Cms\Ui\Nodes\Missing as MissingNode;
use CraftCms\Cms\Ui\Nodes\Separator;
use CraftCms\Cms\Ui\Nodes\Tab;
use CraftCms\Cms\Ui\Nodes\TemplateContent;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;

class UiKitchenSink
{
    private const array TextExpanderTriggers = [
        ['trigger' => ':', 'boundary' => 'whitespace', 'label' => 'Emoji', 'options' => [
            ['label' => '😀', 'value' => '😀', 'keywords' => ['grinning', 'face', 'smile', 'happy']],
            ['label' => '🎉', 'value' => '🎉', 'keywords' => ['party', 'popper', 'celebration', 'confetti']],
            ['label' => '👍', 'value' => '👍', 'keywords' => ['thumbs', 'up', 'approve', 'yes']],
            ['label' => '❤️', 'value' => '❤️', 'keywords' => ['red', 'heart', 'love']],
            ['label' => '🚀', 'value' => '🚀', 'keywords' => ['rocket', 'launch', 'ship']],
        ]],
        ['trigger' => '@', 'boundary' => 'whitespace', 'label' => 'Users', 'source' => 'workbench/text-expander-options'],
    ];

    /** @var array<string, array<string, class-string>> */
    public const array COMPONENTS = [
        'controls' => [
            'address' => Address::class,
            'choice' => Choice::class,
            'color' => Color::class,
            'combobox' => Combobox::class,
            'condition-builder' => ConditionBuilder::class,
            'content-block' => ContentBlock::class,
            'date' => Date::class,
            'date-time' => DateTime::class,
            'element-select' => ElementSelect::class,
            'field-layout-designer' => FieldLayoutDesigner::class,
            'grouped-entry-type-manager' => GroupedEntryTypeManager::class,
            'handle' => Handle::class,
            'icon-picker' => IconPicker::class,
            'lightswitch' => Lightswitch::class,
            'link' => Link::class,
            'markdown' => Markdown::class,
            'matrix' => NestedElementBlocks::class,
            'missing' => MissingControl::class,
            'money' => Money::class,
            'number' => Number::class,
            'permission-tree' => PermissionTree::class,
            'range' => Range::class,
            'table' => Table::class,
            'text' => Text::class,
            'textarea' => Textarea::class,
            'time' => Time::class,
        ],
        'nodes' => [
            'callout' => Callout::class,
            'field' => Field::class,
            'group' => Group::class,
            'heading' => Heading::class,
            'line-break' => LineBreak::class,
            'markdown-content' => MarkdownContent::class,
            'missing' => MissingNode::class,
            'separator' => Separator::class,
            'tab' => Tab::class,
            'template-content' => TemplateContent::class,
        ],
    ];

    public function __construct(private readonly UiResolver $resolver) {}

    public static function component(string $type, string $slug): ?string
    {
        return self::COMPONENTS[$type][$slug] ?? null;
    }

    /** @return array<string, UiPayload>|null */
    public function stories(string $type, string $slug, ?string $countryCode = null): ?array
    {
        $component = self::component($type, $slug);
        $uis = $this->uis($component, $countryCode);

        if ($uis === null) {
            return null;
        }

        return collect($uis)->map(
            fn (Ui $ui, string $name): UiPayload => $this->resolver->resolve(
                $ui,
                new UiContext(
                    namespace: ['kitchenSink', $type, $slug, Str::slug($name)],
                    errors: $name === 'Feedback' && in_array($component, [Text::class, Field::class], true)
                        ? ['text' => ['This is an example validation error.']]
                        : [],
                ),
            ),
        )->all();
    }

    /** @return array<string, Ui>|null */
    private function uis(?string $component, ?string $countryCode): ?array
    {
        if ($component === Address::class) {
            return [
                'Country-dependent fields' => $this->control('Address', Address::make('address')
                    ->countryCode($countryCode ?? 'BE')
                    ->value([])),
                'Current user' => $this->control('Address', Address::make('address')
                    ->countryCode('BE')
                    ->belongsToCurrentUser()
                    ->value([
                        'addressLine1' => 'Korenmarkt 1',
                        'locality' => 'Gent',
                        'postalCode' => '9000',
                    ])),
            ];
        }

        if ($component === Choice::class) {
            $options = [
                ['label' => 'Alpha', 'value' => 'alpha'],
                ['label' => 'Beta', 'value' => 'beta'],
                ['label' => 'Unavailable', 'value' => 'unavailable', 'disabled' => true],
            ];

            return [
                'Select' => $this->control('Choice', Choice::make('choice')
                    ->options($options)
                    ->value('alpha')),
                'Multiple select' => $this->control('Choice', Choice::make('choice')
                    ->multiple()
                    ->presentation(ChoicePresentation::Select)
                    ->options($options)
                    ->value(['alpha', 'beta'])),
                'Checkboxes' => $this->control('Choice', Choice::make('choice')
                    ->multiple()
                    ->presentation(ChoicePresentation::Checkboxes)
                    ->options($options)
                    ->value(['alpha'])),
                'Radios' => $this->control('Choice', Choice::make('choice')
                    ->presentation(ChoicePresentation::Radios)
                    ->options($options)
                    ->value('beta')),
                'Buttons' => $this->control('Choice', Choice::make('choice')
                    ->presentation(ChoicePresentation::Buttons)
                    ->options($options)
                    ->value('alpha')),
                'Multiple buttons' => $this->control('Choice', Choice::make('choice')
                    ->multiple()
                    ->presentation(ChoicePresentation::Buttons)
                    ->options($options)
                    ->value(['alpha', 'beta'])),
            ];
        }

        if ($component === Color::class) {
            return [
                'Picker' => $this->control('Color', Color::make('color')->value('#3b82f6')),
                'Presets' => $this->control('Color', Color::make('color')
                    ->presets(['#ef4444', '#22c55e', '#3b82f6'])
                    ->value('#3b82f6')),
            ];
        }

        if ($component === Combobox::class) {
            $options = [
                ['label' => 'Primary', 'value' => 'primary'],
                ['label' => 'Secondary', 'value' => 'secondary'],
            ];

            return [
                'Selected' => $this->control('Combobox', Combobox::make('combobox')
                    ->options($options)
                    ->value('primary')),
                'Placeholder' => $this->control('Combobox', Combobox::make('combobox')
                    ->options($options)
                    ->placeholder('Choose or enter a value')),
                'Behavior' => $this->control('Combobox', Combobox::make('combobox')
                    ->options($options)
                    ->limit(1)
                    ->clearable()
                    ->requireOptionMatch()
                    ->showAllOnEmpty()
                    ->dir('rtl')
                    ->value('primary')),
            ];
        }

        if ($component === ConditionBuilder::class) {
            return [
                'Default' => $this->control('Condition builder', ConditionBuilder::make('conditionBuilder')
                    ->conditionClass(ElementCondition::class)
                    ->value(['elementType' => Entry::class])),
                'Project config' => $this->control('Condition builder', ConditionBuilder::make('conditionBuilder')
                    ->conditionClass(ElementCondition::class)
                    ->forProjectConfig()
                    ->value(['elementType' => Entry::class])),
            ];
        }

        if ($component === ContentBlock::class) {
            $ui = Ui::make([
                Field::make('Nested text', Text::make('body')->value('Content block value')),
            ]);

            return [
                'Empty' => $this->control('Content block', ContentBlock::make('contentBlock')->ui($ui)),
                'Populated' => $this->control('Content block', ContentBlock::make('contentBlock')
                    ->ui($ui)
                    ->value(['body' => 'Content block value'])),
            ];
        }

        if ($component === Date::class) {
            return [
                'Default' => $this->control('Date', Date::make('date')->value('2026-08-06')),
                'Constrained' => $this->control('Date', Date::make('date')
                    ->min('2026-08-01')
                    ->max('2026-08-31')
                    ->value('2026-08-06')),
            ];
        }

        if ($component === DateTime::class) {
            return [
                'Date' => $this->control('Date and time', DateTime::make('dateTime')->value([
                    'date' => '2026-08-06',
                ])),
                'Time' => $this->control('Date and time', DateTime::make('dateTime')
                    ->showDate(false)
                    ->showTime()
                    ->minuteIncrement(15)
                    ->value(['time' => '14:30'])),
                'Date and time' => $this->control('Date and time', DateTime::make('dateTime')
                    ->showTime()
                    ->minuteIncrement(15)
                    ->value([
                        'date' => '2026-08-06',
                        'time' => '14:30',
                    ])),
                'With time zone' => $this->control('Date and time', DateTime::make('dateTime')
                    ->showTime()
                    ->showTimeZone()
                    ->minuteIncrement(15)
                    ->value([
                        'date' => '2026-08-06',
                        'time' => '14:30',
                        'timezone' => 'Europe/Brussels',
                    ])),
            ];
        }

        if ($component === ElementSelect::class) {
            return [
                'Single' => $this->control('Element select', ElementSelect::make('elementSelect')
                    ->elementType(Entry::class)
                    ->sources(['*'])
                    ->selectionLabel('Choose an entry')
                    ->limit(1)),
                'Multiple' => $this->control('Element select', ElementSelect::make('elementSelect')
                    ->elementType(Entry::class)
                    ->sources(['*'])
                    ->selectionLabel('Choose entries')),
            ];
        }

        if ($component === FieldLayoutDesigner::class) {
            $value = [
                'uid' => '8c3d870b-fb40-4d02-b813-fb99ae33b6bf',
                'type' => Entry::class,
            ];

            return [
                'Customizable tabs' => $this->control(
                    'Field layout designer',
                    FieldLayoutDesigner::make('fieldLayoutDesigner')->elementType(Entry::class)->value($value),
                ),
                'Fixed tabs' => $this->control(
                    'Field layout designer',
                    FieldLayoutDesigner::make('fieldLayoutDesigner')
                        ->elementType(Entry::class)
                        ->customizableTabs(false)
                        ->value($value),
                ),
            ];
        }

        if ($component === Handle::class) {
            return [
                'Default' => $this->control('Handle', Handle::make('handle')->value('exampleHandle')),
                'Generated' => Ui::make([
                    Field::make('Name', Text::make('name')->value('Example handle')),
                    Field::make('Handle', Handle::make('handle')->source('name')->value('exampleHandle')),
                ]),
            ];
        }

        if ($component === IconPicker::class) {
            return [
                'Default' => $this->control('Icon picker', IconPicker::make('iconPicker')->value('star')),
                'Free only' => $this->control('Icon picker', IconPicker::make('iconPicker')->freeOnly()->value('star')),
            ];
        }

        if ($component === Lightswitch::class) {
            return [
                'Off' => $this->control('Lightswitch', Lightswitch::make('lightswitch')->value(false)),
                'On' => $this->control('Lightswitch', Lightswitch::make('lightswitch')->value(true)),
                'Indeterminate' => $this->control('Lightswitch', Lightswitch::make('lightswitch')->indeterminate()),
                'Small' => $this->control('Lightswitch', Lightswitch::make('lightswitch')->size('small')->value(true)),
                'Labels' => $this->control('Lightswitch', Lightswitch::make('lightswitch')
                    ->onLabel('On')
                    ->offLabel('Off')
                    ->value(true)),
            ];
        }

        if ($component === Link::class) {
            $types = [
                ['id' => 'url', 'label' => 'URL', 'kind' => 'text'],
                ['id' => 'email', 'label' => 'Email', 'kind' => 'text'],
            ];

            return [
                'Basic' => $this->control('Link', Link::make('link')
                    ->types($types)
                    ->value([
                        'type' => 'url',
                        'value' => 'https://craftcms.com',
                    ])),
                'Label and advanced fields' => $this->control('Link', Link::make('link')
                    ->types($types)
                    ->showLabelField()
                    ->advancedFields(['urlSuffix', 'title'])
                    ->value([
                        'type' => 'url',
                        'value' => 'https://craftcms.com',
                        'label' => 'Craft CMS',
                        'title' => 'Craft CMS',
                    ])),
            ];
        }

        if ($component === Markdown::class) {
            return [
                'Toolbar' => $this->control('Markdown', Markdown::make('markdown')
                    ->toolbarButtons(MarkdownField::DEFAULT_TOOLBAR_BUTTONS)
                    ->value('**Markdown** value')),
                'No toolbar' => $this->control('Markdown', Markdown::make('markdown')
                    ->showToolbar(false)
                    ->value('**Markdown** value')),
                'Emoji and user mentions' => $this->control('Markdown', Markdown::make('markdown')
                    ->placeholder('Type : for emoji or @ to mention a user')
                    ->textExpanderTriggers(self::TextExpanderTriggers)),
            ];
        }

        if ($component === NestedElementBlocks::class) {
            $ui = Ui::make([
                Field::make('Nested heading', Text::make('heading')->value('Matrix block value')),
            ]);
            $value = [
                'entries' => ['example-block' => [
                    'type' => 'text',
                    'heading' => 'Matrix block value',
                ]],
                'sortOrder' => ['example-block'],
            ];

            return [
                'Empty' => $this->control('Matrix', NestedElementBlocks::make('matrix')
                    ->entryTypes(['text' => 'Text'])
                    ->uis(['example-block' => $ui])),
                'Populated' => $this->control('Matrix', NestedElementBlocks::make('matrix')
                    ->entryTypes(['text' => 'Text'])
                    ->uis(['example-block' => $ui])
                    ->value($value)),
                'Entry limits' => $this->control('Matrix', NestedElementBlocks::make('matrix')
                    ->entryTypes(['text' => 'Text'])
                    ->uis(['example-block' => $ui])
                    ->minEntries(1)
                    ->maxEntries(1)
                    ->value($value)),
            ];
        }

        if ($component === Money::class) {
            return [
                'Currency' => $this->control('Money', Money::make('money')
                    ->currency('EUR')
                    ->locale('en-US')
                    ->value(['value' => '125.50', 'locale' => 'en-US'])),
                'Without currency' => $this->control('Money', Money::make('money')
                    ->currency('EUR')
                    ->locale('en-US')
                    ->showCurrency(false)
                    ->value(['value' => '125.50', 'locale' => 'en-US'])),
            ];
        }

        if ($component === Number::class) {
            return [
                'Default' => $this->control('Number', Number::make('number')->value(42)),
                'Constrained' => $this->control('Number', Number::make('number')
                    ->min(0)
                    ->max(100)
                    ->step(5)
                    ->size(5)
                    ->value(40)),
            ];
        }

        if ($component === PermissionTree::class) {
            $groups = [
                new PermissionGroup('content', 'Content', collect([
                    new Permission('viewEntries', 'View entries', nested: collect([
                        new Permission('editEntries', 'Edit entries'),
                        new Permission('deleteEntries', 'Delete entries', warning: 'Deleted entries cannot be restored.'),
                    ])),
                ])),
                new PermissionGroup('system', 'System', collect([
                    new Permission('accessCp', 'Access the control panel'),
                ])),
            ];

            return [
                'Selected and inherited' => $this->control('Permissions', PermissionTree::make('permissions')
                    ->groups($groups)
                    ->lockedPermissions(['accessCp'])
                    ->value(['viewEntries', 'editEntries'])),
            ];
        }

        if ($component === Range::class) {
            return [
                'Default' => $this->control('Range', Range::make('range')->value(50)),
                'Stepped' => $this->control('Range', Range::make('range')
                    ->min(0)
                    ->max(100)
                    ->step(10)
                    ->value(60)),
            ];
        }

        if ($component === Table::class) {
            $columns = [
                'name' => ['heading' => 'Name', 'type' => 'singleline'],
                'enabled' => ['heading' => 'Enabled', 'type' => 'checkbox'],
            ];

            return [
                'Fixed rows' => $this->control('Table', Table::make('table')
                    ->columns($columns)
                    ->value([['name' => 'Example row', 'enabled' => true]])),
                'Editable rows' => $this->control('Table', Table::make('table')
                    ->columns($columns)
                    ->allowAdd()
                    ->allowDelete()
                    ->allowReorder()
                    ->value([['name' => 'Example row', 'enabled' => true]])),
                'Keyed rows' => $this->control('Table', Table::make('table')
                    ->columns($columns)
                    ->allowAdd()
                    ->allowDelete()
                    ->allowReorder()
                    ->keyed()
                    ->value(['first' => ['name' => 'Example row', 'enabled' => true]])),
            ];
        }

        if ($component === Text::class) {
            return [
                'Default' => $this->control('Text', Text::make('text')->value('Text value')),
                'Email' => $this->control('Text', Text::make('text')
                    ->inputType('email')
                    ->value('dev@craftcms.com')),
                'Password' => $this->control('Text', Text::make('text')
                    ->inputType('password')
                    ->value('secret')),
                'Monospace' => $this->control('Text', Text::make('text')
                    ->monospace()
                    ->value('font-family: monospace;')),
                'Emoji and user mentions' => $this->control('Text', Text::make('text')
                    ->placeholder('Type : for emoji or @ to mention a user')
                    ->textExpanderTriggers(self::TextExpanderTriggers)),
                'Browser behavior' => $this->control('Text', Text::make('text')
                    ->inputMode('numeric')
                    ->autocomplete('one-time-code')
                    ->autocorrect(false)
                    ->autocapitalize(false)
                    ->size(6)
                    ->dir('rtl')
                    ->value('123456')),
                'Feedback' => Ui::make([
                    Field::make('Text', Text::make('text')->placeholder('Plain text')->value('Text value'))
                        ->instructions('Includes instructions, a tip, a warning, and an error.')
                        ->required()
                        ->tip('Example tip')
                        ->warning('Example warning'),
                ]),
            ];
        }

        if ($component === Textarea::class) {
            return [
                'Default' => $this->control('Textarea', Textarea::make('textarea')
                    ->rows(4)
                    ->value("First line\nSecond line")),
                'Monospace' => $this->control('Textarea', Textarea::make('textarea')
                    ->rows(4)
                    ->monospace()
                    ->value("first_key: value\nsecond_key: value")),
                'Emoji and user mentions' => $this->control('Textarea', Textarea::make('textarea')
                    ->rows(4)
                    ->placeholder('Type : for emoji or @ to mention a user')
                    ->textExpanderTriggers(self::TextExpanderTriggers)),
            ];
        }

        if ($component === Time::class) {
            return [
                'Default' => $this->control('Time', Time::make('time')->value('14:30')),
                '15-minute steps' => $this->control('Time', Time::make('time')->step(900)->value('14:30')),
            ];
        }

        if ($component === Callout::class) {
            return [
                'Info' => Ui::make([
                    Callout::make('callout-info', 'This is an **informational** callout.'),
                ]),
                'Success' => Ui::make([
                    Callout::make('callout-success', 'This is a **success** callout.')->variant('success'),
                ]),
                'Warning' => Ui::make([
                    Callout::make('callout-warning', 'This is a **warning** callout.')->variant('warning'),
                ]),
                'Danger' => Ui::make([
                    Callout::make('callout-danger', 'This is a **danger** callout.')->variant('danger'),
                ]),
                'Dismissible' => Ui::make([
                    Callout::make('callout-dismissible', 'This callout can be dismissed.')->dismissible(),
                ]),
                'Plain with custom icon' => Ui::make([
                    Callout::make('callout-plain', 'This is a **plain** callout with a custom icon.')
                        ->appearance('plain')
                        ->icon('flask'),
                ]),
            ];
        }

        if ($component === Field::class) {
            return [
                'Default' => Ui::make([
                    Field::make('Field', Text::make('text')->value('Field value')),
                ]),
                'Required' => Ui::make([
                    Field::make('Field', Text::make('text')->value('Field value'))->required(),
                ]),
                'Instructions after' => Ui::make([
                    Field::make('Field', Text::make('text')->value('Field value'))
                        ->instructions('Instructions shown after the control.')
                        ->instructionsPosition('after'),
                ]),
                'Feedback' => Ui::make([
                    Field::make('Field', Text::make('text')->value('Field value'))
                        ->instructions('Field instructions')
                        ->tip('Example tip')
                        ->warning('Example warning'),
                ]),
            ];
        }

        if ($component === Group::class) {
            return [
                'Fieldset' => Ui::make([
                    Group::make('group-fieldset', [
                        MarkdownContent::make('group-fieldset-content', 'Content inside the **Group** node.')
                            ->displayInPane(false),
                    ])->label('Group node'),
                ]),
                'Collapsible' => Ui::make([
                    Group::make('group-collapsible', [
                        MarkdownContent::make('group-collapsible-content', 'Content inside the **Group** node.')
                            ->displayInPane(false),
                    ])->label('Group node')->collapsible(),
                ]),
            ];
        }

        if ($component === Heading::class) {
            return [
                'Default' => Ui::make([
                    Heading::make('heading-default', 'Default heading'),
                ]),
                'Levels' => Ui::make([
                    Heading::make('heading-level-1', 'Level 1')->level(1),
                    Heading::make('heading-level-2', 'Level 2')->level(2),
                    Heading::make('heading-level-3', 'Level 3')->level(3),
                    Heading::make('heading-level-4', 'Level 4')->level(4),
                    Heading::make('heading-level-5', 'Level 5')->level(5),
                    Heading::make('heading-level-6', 'Level 6')->level(6),
                ]),
            ];
        }

        if ($component === MarkdownContent::class) {
            return [
                'Pane' => Ui::make([
                    MarkdownContent::make('markdown-content-pane', 'This is the **Markdown Content** node.'),
                ]),
                'Plain' => Ui::make([
                    MarkdownContent::make('markdown-content-plain', 'This is the **Markdown Content** node.')
                        ->displayInPane(false),
                ]),
            ];
        }

        $ui = $this->ui($component);

        return $ui === null ? null : ['Default' => $ui];
    }

    private function ui(?string $component): ?Ui
    {
        return match ($component) {
            GroupedEntryTypeManager::class => $this->control(
                'Grouped entry type manager',
                GroupedEntryTypeManager::make('groupedEntryTypeManager')->value([]),
            ),
            MissingControl::class => $this->control(
                'Missing control',
                MissingControl::make('missingControl')->provider('workbench/missing-control'),
            ),
            LineBreak::class => Ui::make([
                Heading::make('before-line-break', 'Before line break')->width(50),
                LineBreak::make('line-break-node'),
                Heading::make('after-line-break', 'After line break')->width(50),
            ]),
            MissingNode::class => Ui::make([
                MissingNode::make('missing-node', 'workbench/missing-node'),
            ]),
            Separator::class => Ui::make([
                Heading::make('before-separator', 'Before separator'),
                Separator::make('separator-node'),
                Heading::make('after-separator', 'After separator'),
            ]),
            Tab::class => Ui::make([
                Tab::make('first-tab', 'First tab', [
                    MarkdownContent::make('first-tab-content', 'First tab content')->displayInPane(false),
                ]),
                Tab::make('second-tab', 'Second tab', [
                    MarkdownContent::make('second-tab-content', 'Second tab content')->displayInPane(false),
                ]),
            ]),
            TemplateContent::class => Ui::make([
                TemplateContent::make(
                    'template-content-node',
                    '<p>This is the <strong>Template Content</strong> node.</p>',
                ),
            ]),
            default => null,
        };
    }

    private function control(string $label, Control $control): Ui
    {
        return Ui::make([Field::make($label, $control)]);
    }
}
