<?php

declare(strict_types=1);

use craft\events\DefineAltActionsEvent;
use craft\events\DefineAttributeHtmlEvent;
use craft\events\DefineHtmlEvent;
use craft\events\DefineMenuItemsEvent;
use craft\events\DefineMetadataEvent;
use craft\events\RegisterElementHtmlAttributesEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\User\Elements\User;
use yii\base\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create a test entry for each test
    $this->entry = EntryModel::factory()->create();

    // Load it from an ElementQuery so all data is properly set
    $this->entry = entryQuery()->id($this->entry->id)->one();

    actingAs(User::findOne());
});

describe('getCpEditUrl', function () {
    test('returns null when element has no id', function () {
        $entry = new Entry;

        expect($entry->getCpEditUrl())->toBeNull();
    });

    test('returns url when element has id and cpEditUrl is available', function () {
        $url = $this->entry->getCpEditUrl();

        expect($url)->toBeString()->toContain(Cms::config()->cpTrigger);
    });
});

describe('getPostEditUrl', function () {
    test('returns url for entry elements', function () {
        $url = $this->entry->getPostEditUrl();

        expect($url)->toBeString()->toContain(Cms::config()->cpTrigger);
    });
});

describe('getAdditionalButtons', function () {
    test('returns empty string by default', function () {
        expect((string) $this->entry->getAdditionalButtons())->toBe('');
    });

    test('triggers defineAdditionalButtons event', function () {
        $eventTriggered = false;
        $customHtml = '<button>Custom Button</button>';

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ADDITIONAL_BUTTONS,
            function (DefineHtmlEvent $event) use (&$eventTriggered, $customHtml) {
                $eventTriggered = true;
                $event->html = $customHtml;
            }
        );

        $buttons = $this->entry->getAdditionalButtons();

        expect($eventTriggered)->toBeTrue();
        expect((string) $buttons)->toBe($customHtml);

        Event::off(Entry::class, Entry::EVENT_DEFINE_ADDITIONAL_BUTTONS);
    });
});

describe('getAltActions', function () {
    test('returns default alt actions for canonical element', function () {
        $altActions = $this->entry->getAltActions();

        expect($altActions)->toBeArray()->not->toBeEmpty();

        $labels = collect($altActions)->pluck('label');
        expect($labels)->toContain('Save and continue editing');
    });

    test('includes shortcut and retainScroll for continue editing action', function () {
        $altActions = $this->entry->getAltActions();

        $continueEditingAction = collect($altActions)
            ->firstWhere('label', 'Save and continue editing');

        expect($continueEditingAction)->toHaveKey('shortcut', true);
        expect($continueEditingAction)->toHaveKey('retainScroll', true);
        expect($continueEditingAction)->toHaveKey('redirect');
    });

    test('triggers defineAltActions event', function () {
        $eventTriggered = false;
        $customAction = [
            'label' => 'Custom Action',
            'action' => 'custom/action',
        ];

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ALT_ACTIONS,
            function (DefineAltActionsEvent $event) use (&$eventTriggered, $customAction) {
                $eventTriggered = true;
                $event->altActions[] = $customAction;
            }
        );

        $altActions = $this->entry->getAltActions();

        expect($eventTriggered)->toBeTrue();
        expect($altActions)->toContain($customAction);

        Event::off(Entry::class, Entry::EVENT_DEFINE_ALT_ACTIONS);
    });

    test('event can modify alt actions', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ALT_ACTIONS,
            function (DefineAltActionsEvent $event) {
                $event->altActions = [
                    [
                        'label' => 'Only Action',
                        'action' => 'test/action',
                    ],
                ];
            }
        );

        $altActions = $this->entry->getAltActions();

        expect($altActions)->toHaveCount(1);
        expect($altActions[0]['label'])->toBe('Only Action');

        Event::off(Entry::class, Entry::EVENT_DEFINE_ALT_ACTIONS);
    });
});

describe('getActionMenuItems', function () {
    test('returns array of menu items', function () {
        $items = $this->entry->getActionMenuItems();

        expect($items)->toBeArray();
    });

    test('includes destructive items with destructive flag', function () {
        $items = $this->entry->getActionMenuItems();

        $hasDestructiveItems = collect($items)
            ->contains(fn ($item) => ($item['destructive'] ?? false) === true);

        expect($items)->toBeArray();
        expect($hasDestructiveItems)->toBeTrue();
    });

    test('triggers defineActionMenuItems event', function () {
        $eventTriggered = false;
        $customItem = [
            'id' => 'custom-action',
            'label' => 'Custom Menu Item',
            'icon' => 'wand',
        ];

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ACTION_MENU_ITEMS,
            function (DefineMenuItemsEvent $event) use (&$eventTriggered, $customItem) {
                $eventTriggered = true;
                $event->items[] = $customItem;
            }
        );

        $items = $this->entry->getActionMenuItems();

        expect($eventTriggered)->toBeTrue();
        expect($items)->toContain($customItem);

        Event::off(Entry::class, Entry::EVENT_DEFINE_ACTION_MENU_ITEMS);
    });

    test('event can modify menu items', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ACTION_MENU_ITEMS,
            function (DefineMenuItemsEvent $event) {
                $event->items = [
                    [
                        'id' => 'only-item',
                        'label' => 'Only Item',
                    ],
                ];
            }
        );

        $items = $this->entry->getActionMenuItems();

        expect($items)->toHaveCount(1);
        expect($items[0]['label'])->toBe('Only Item');

        Event::off(Entry::class, Entry::EVENT_DEFINE_ACTION_MENU_ITEMS);
    });
});

describe('getHtmlAttributes', function () {
    test('returns array with data attributes', function () {
        $attributes = $this->entry->getHtmlAttributes('index');

        expect($attributes)->toBeArray()->toHaveKey('data');
    });

    test('includes disallow-status data attribute', function () {
        $attributes = $this->entry->getHtmlAttributes('index');

        expect($attributes['data'])->toHaveKey('disallow-status');
    });

    test('triggers registerHtmlAttributes event', function () {
        $eventTriggered = false;
        $customAttribute = 'custom-value';

        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_HTML_ATTRIBUTES,
            function (RegisterElementHtmlAttributesEvent $event) use (&$eventTriggered, $customAttribute) {
                $eventTriggered = true;
                $event->htmlAttributes['data']['custom'] = $customAttribute;
            }
        );

        $attributes = $this->entry->getHtmlAttributes('index');

        expect($eventTriggered)->toBeTrue();
        expect($attributes['data']['custom'])->toBe($customAttribute);

        Event::off(Entry::class, Entry::EVENT_REGISTER_HTML_ATTRIBUTES);
    });

    test('event can modify html attributes', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_HTML_ATTRIBUTES,
            function (RegisterElementHtmlAttributesEvent $event) {
                $event->htmlAttributes = [
                    'class' => 'custom-class',
                    'data' => [
                        'test' => 'value',
                    ],
                ];
            }
        );

        $attributes = $this->entry->getHtmlAttributes('index');

        expect($attributes)->toHaveKey('class', 'custom-class');
        expect($attributes['data'])->toHaveKey('test', 'value');

        Event::off(Entry::class, Entry::EVENT_REGISTER_HTML_ATTRIBUTES);
    });
});

describe('getAttributeHtml', function () {
    test('returns string for valid attribute', function () {
        $html = $this->entry->getAttributeHtml('title');

        expect($html)->toBeString();
    });

    test('triggers defineAttributeHtml event', function () {
        $eventTriggered = false;
        $customHtml = '<span class="custom">Custom HTML</span>';

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ATTRIBUTE_HTML,
            function (DefineAttributeHtmlEvent $event) use (&$eventTriggered, $customHtml) {
                $eventTriggered = true;
                if ($event->attribute === 'title') {
                    $event->html = $customHtml;
                }
            }
        );

        $html = $this->entry->getAttributeHtml('title');

        expect($eventTriggered)->toBeTrue();
        expect((string) $html)->toBe($customHtml);

        Event::off(Entry::class, Entry::EVENT_DEFINE_ATTRIBUTE_HTML);
    });

    test('event receives correct attribute name', function () {
        $capturedAttribute = null;

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_ATTRIBUTE_HTML,
            function (DefineAttributeHtmlEvent $event) use (&$capturedAttribute) {
                $capturedAttribute = $event->attribute;
            }
        );

        $this->entry->getAttributeHtml('slug');

        expect($capturedAttribute)->toBe('slug');

        Event::off(Entry::class, Entry::EVENT_DEFINE_ATTRIBUTE_HTML);
    });
});

describe('getInlineAttributeInputHtml', function () {
    test('returns string for valid attribute', function () {
        $html = $this->entry->getInlineAttributeInputHtml('title');

        expect($html)->toBeString();
    });

    test('triggers defineInlineAttributeInputHtml event', function () {
        $eventTriggered = false;
        $customHtml = '<input type="text" value="Custom">';

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML,
            function (DefineAttributeHtmlEvent $event) use (&$eventTriggered, $customHtml) {
                $eventTriggered = true;
                if ($event->attribute === 'title') {
                    $event->html = $customHtml;
                }
            }
        );

        $html = $this->entry->getInlineAttributeInputHtml('title');

        expect($eventTriggered)->toBeTrue();
        expect((string) $html)->toBe($customHtml);

        Event::off(Entry::class, Entry::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML);
    });

    test('event receives correct attribute name', function () {
        $capturedAttribute = null;

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML,
            function (DefineAttributeHtmlEvent $event) use (&$capturedAttribute) {
                $capturedAttribute = $event->attribute;
            }
        );

        $this->entry->getInlineAttributeInputHtml('slug');

        expect($capturedAttribute)->toBe('slug');

        Event::off(Entry::class, Entry::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML);
    });
});

describe('getSidebarHtml', function () {
    test('returns string for static mode', function () {
        $html = $this->entry->getSidebarHtml(true);

        expect($html)->toBeString();
    });

    test('returns string for non-static mode', function () {
        $html = $this->entry->getSidebarHtml(false);

        expect($html)->toBeString();
    });

    test('triggers defineSidebarHtml event', function () {
        $eventTriggered = false;
        $customHtml = '<div class="custom-sidebar">Custom</div>';

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_SIDEBAR_HTML,
            function (DefineHtmlEvent $event) use (&$eventTriggered, $customHtml) {
                $eventTriggered = true;
                $event->html = $customHtml;
            }
        );

        $html = $this->entry->getSidebarHtml(false);

        expect($eventTriggered)->toBeTrue();
        expect((string) $html)->toBe($customHtml);

        Event::off(Entry::class, Entry::EVENT_DEFINE_SIDEBAR_HTML);
    });

    test('event can append to existing html', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_SIDEBAR_HTML,
            function (DefineHtmlEvent $event) {
                $event->html .= '<div class="appended">Appended</div>';
            }
        );

        $html = $this->entry->getSidebarHtml(false);

        expect((string) $html)->toContain('Appended');

        Event::off(Entry::class, Entry::EVENT_DEFINE_SIDEBAR_HTML);
    });
});

describe('getMetadata', function () {
    test('returns array of metadata', function () {
        $metadata = $this->entry->getMetadata();

        expect($metadata)->toBeArray()->not->toBeEmpty();
    });

    test('includes ID in metadata', function () {
        $metadata = $this->entry->getMetadata();

        expect($metadata)->toHaveKey('ID');
    });

    test('includes Status in metadata when element has statuses', function () {
        $metadata = $this->entry->getMetadata();

        expect($metadata)->toHaveKey('Status');
    });

    test('includes Created at timestamp', function () {
        $metadata = $this->entry->getMetadata();

        expect($metadata)->toHaveKey('Created at');
    });

    test('includes Updated at timestamp', function () {
        $metadata = $this->entry->getMetadata();

        expect($metadata)->toHaveKey('Updated at');
    });

    test('triggers defineMetadata event', function () {
        $eventTriggered = false;

        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_METADATA,
            function (DefineMetadataEvent $event) use (&$eventTriggered) {
                $eventTriggered = true;
                $event->metadata['Custom'] = 'Custom Value';
            }
        );

        $metadata = $this->entry->getMetadata();

        expect($eventTriggered)->toBeTrue();
        expect($metadata)->toHaveKey('Custom');
        expect($metadata['Custom'])->toBe('Custom Value');

        Event::off(Entry::class, Entry::EVENT_DEFINE_METADATA);
    });

    test('event can modify existing metadata', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_METADATA,
            function (DefineMetadataEvent $event) {
                $event->metadata = [
                    'Only Key' => 'Only Value',
                ];
            }
        );

        $metadata = $this->entry->getMetadata();

        expect($metadata)->toHaveKey('Only Key');
        expect($metadata)->toHaveKey('ID'); // Should still have merged defaults
        expect($metadata)->toHaveKey('Status');

        Event::off(Entry::class, Entry::EVENT_DEFINE_METADATA);
    });

    test('metadata values can be callables', function () {
        $metadata = $this->entry->getMetadata();

        // Status is defined as a callable
        expect($metadata['Status'])->toBeCallable();

        // When called, it should return something
        $statusValue = call_user_func($metadata['Status']);
        expect($statusValue)->not->toBeNull();
    });

    test('callable metadata can return false to omit', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_DEFINE_METADATA,
            function (DefineMetadataEvent $event) {
                $event->metadata['Hidden'] = fn () => false;
            }
        );

        $metadata = $this->entry->getMetadata();

        expect($metadata)->toHaveKey('Hidden');

        // The callable returns false, which indicates it should be omitted when rendered
        $hiddenValue = call_user_func($metadata['Hidden']);
        expect($hiddenValue)->toBeFalse();

        Event::off(Entry::class, Entry::EVENT_DEFINE_METADATA);
    });
});

describe('prepareEditScreen', function () {
    test('can be called without errors', function () {
        $response = new CpScreenResponse;
        $containerId = 'test-container-id';

        expect(fn () => $this->entry->prepareEditScreen($response, $containerId))
            ->not->toThrow(Exception::class);
    });
});

describe('getCrumbs', function () {
    test('returns array', function () {
        expect($this->entry->getCrumbs())->toBeArray();
    });
});

describe('getUiLabel and setUiLabel', function () {
    test('returns string representation by default', function () {
        expect($this->entry->getUiLabel())->toBeString();
    });

    test('returns custom label when set', function () {
        $customLabel = 'Custom UI Label';
        $this->entry->setUiLabel($customLabel);

        expect($this->entry->getUiLabel())->toBe($customLabel);
    });

    test('setting null reverts to default', function () {
        $this->entry->setUiLabel('Custom');
        $this->entry->setUiLabel(null);

        expect($this->entry->getUiLabel())->toBe($this->entry->title);
    });
});

describe('getUiLabelPath and setUiLabelPath', function () {
    test('returns empty array by default', function () {
        expect($this->entry->getUiLabelPath())->toBe([]);
    });

    test('returns custom path when set', function () {
        $path = ['Section', 'Category'];
        $this->entry->setUiLabelPath($path);

        expect($this->entry->getUiLabelPath())->toBe($path);
    });
});

describe('getChipLabelHtml', function () {
    test('returns encoded UI label', function () {
        $html = $this->entry->getChipLabelHtml();

        expect((string) $html)->toBeString()->not->toBeEmpty();
    });

    test('encodes HTML entities', function () {
        $this->entry->setUiLabel('<script>alert("xss")</script>');

        $html = $this->entry->getChipLabelHtml();

        expect((string) $html)->not->toContain('<script>');
        expect((string) $html)->toContain('&lt;script&gt;');
    });
});

describe('showStatusIndicator', function () {
    test('returns true for elements with statuses', function () {
        expect($this->entry->showStatusIndicator())->toBeTrue();
    });
});

describe('getCardTitle', function () {
    test('returns string for entries', function () {
        expect($this->entry->getCardTitle())->toBeString();
    });
});

describe('getCardBodyHtml', function () {
    test('returns string', function () {
        expect($this->entry->getCardBodyHtml())->toBeString();
    });
});

describe('getRef', function () {
    test('returns reference string', function () {
        expect($this->entry->getRef())->toBeString();
    });
});

describe('createAnother', function () {
    test('returns new element instance for entries', function () {
        $newElement = $this->entry->createAnother();

        expect($newElement)->toBeInstanceOf(Entry::class);
        expect($newElement->id)->toBeNull();
    });
});
