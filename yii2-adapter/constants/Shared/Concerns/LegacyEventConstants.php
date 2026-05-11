<?php

declare(strict_types=1);
namespace CraftCms\Cms\Shared\Concerns;

use craft\base\Event as YiiEvent;
use craft\base\FieldLayoutComponent;
use craft\base\FieldLayoutElement;
use craft\events\DefineFieldActionsEvent;
use craft\events\DefineShowFieldLayoutComponentInFormEvent;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutActionMenuItemsResolving;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use Illuminate\Support\Facades\Event;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyEventConstants
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    // Section

    public const TYPE_SINGLE = SectionType::Single->value;
    public const TYPE_CHANNEL = SectionType::Channel->value;
    public const TYPE_STRUCTURE = SectionType::Structure->value;

    public const PROPAGATION_METHOD_NONE = PropagationMethod::None->value;
    public const PROPAGATION_METHOD_SITE_GROUP = PropagationMethod::SiteGroup->value;
    public const PROPAGATION_METHOD_LANGUAGE = PropagationMethod::Language->value;
    public const PROPAGATION_METHOD_ALL = PropagationMethod::All->value;
    /** @since 3.5.0 */
    public const PROPAGATION_METHOD_CUSTOM = PropagationMethod::Custom->value;

    /** @since 3.7.0 */
    public const DEFAULT_PLACEMENT_BEGINNING = DefaultPlacement::Beginning->value;
    /** @since 3.7.0 */
    public const DEFAULT_PLACEMENT_END = DefaultPlacement::End->value;

    // FieldLayoutComponent

    /**
     * @event DefineShowFieldLayoutComponentInFormEvent The event that is triggered when determining whether the component should be shown in a field layout.
     * @see showInForm()
     * @since 5.3.0
     */
    public const EVENT_DEFINE_SHOW_IN_FORM = 'defineShowInForm';

    // BaseField layoutelement

    /**
     * @event DefineFieldActionsEvent The event that is triggered when defining action menu items.
     *
     * @see actionMenuItems()
     * @since 5.9.0
     */
    public const EVENT_DEFINE_ACTION_MENU_ITEMS = 'defineActionMenuItems';

    public static function registerEvents(): void
    {
        Event::listen(function(FieldLayoutComponentShowInFormResolving $event) {
            if (YiiEvent::hasHandlers(FieldLayoutComponent::class, FieldLayoutComponent::EVENT_DEFINE_SHOW_IN_FORM)) {
                $yiiEvent = new DefineShowFieldLayoutComponentInFormEvent([
                    'fieldLayout' => $event->fieldLayout,
                    'element' => $event->element,
                ]);
                $yiiEvent->sender = $event->fieldLayoutComponent;

                YiiEvent::trigger(FieldLayoutComponent::class, FieldLayoutComponent::EVENT_DEFINE_SHOW_IN_FORM, $yiiEvent);

                $event->showInForm = $yiiEvent->showInForm;
                $event->handled = $yiiEvent->handled;
            }
        });

        Event::listen(function(FieldLayoutActionMenuItemsResolving $event) {
            if (YiiEvent::hasHandlers(FieldLayoutElement::class, FieldLayoutElement::EVENT_DEFINE_ACTION_MENU_ITEMS)) {
                $yiiEvent = new DefineFieldActionsEvent([
                    'element' => $event->element,
                    'static' => $event->static,
                    'items' => $event->items,
                ]);

                YiiEvent::trigger(FieldLayoutElement::class, FieldLayoutElement::EVENT_DEFINE_ACTION_MENU_ITEMS, $yiiEvent);

                $event->items = $yiiEvent->items;
            }
        });
    }
}
