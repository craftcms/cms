<?php

declare(strict_types=1);

namespace craft\base;

use craft\base\Event as YiiEvent;
use craft\events\DefineShowFieldLayoutComponentInFormEvent;
use CraftCms\Cms\FieldLayout\Events\DefineShowInForm;
use Illuminate\Support\Facades\Event;

/**
 * FieldLayoutComponent is the base class for classes representing field layout components (tabs or elements) in terms of objects.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\FieldLayout\FieldLayoutComponent} instead.
 */
class FieldLayoutComponent extends \CraftCms\Cms\FieldLayout\FieldLayoutComponent
{
    /**
     * @event DefineShowFieldLayoutComponentInFormEvent The event that is triggered when determining whether the component should be shown in a field layout.
     * @see showInForm()
     * @since 5.3.0
     */
    public const EVENT_DEFINE_SHOW_IN_FORM = 'defineShowInForm';

    public static function registerEvents(): void
    {
        Event::listen(function(DefineShowInForm $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_DEFINE_SHOW_IN_FORM)) {
                $yiiEvent = new DefineShowFieldLayoutComponentInFormEvent([
                    'fieldLayout' => $event->fieldLayout,
                    'element' => $event->element,
                ]);
                $yiiEvent->sender = $event->fieldLayoutComponent;

                YiiEvent::trigger(self::class, self::EVENT_DEFINE_SHOW_IN_FORM, $yiiEvent);

                $event->showInForm = $yiiEvent->showInForm;
                $event->handled = $yiiEvent->handled;
            }
        });
    }
}
