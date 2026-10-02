<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use craft\base\Event as YiiEvent;
use craft\events\DefineElementEditorHtmlEvent;
use craft\web\Controller;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\ElementEditorContentResolving;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Support\Facades\Event;

/**
 * Elements controller.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
 */
class ElementsController extends Controller
{
    /**
     * @event DefineElementEditorHtmlEvent The event that is triggered when rendering an element editor’s content.
     * @see _editorContent()
     */
    public const string EVENT_DEFINE_EDITOR_CONTENT = 'defineEditorContent';

    public static function registerEvents(): void
    {
        Event::listen(function(ElementEditorContentResolving $event) {
            if (!YiiEvent::hasHandlers(ElementsController::class, ElementsController::EVENT_DEFINE_EDITOR_CONTENT)) {
                return;
            }

            $yiiEvent = new DefineElementEditorHtmlEvent([
                'element' => $event->element,
                'html' => $event->html,
                'static' => $event->static,
            ]);

            YiiEvent::trigger(ElementsController::class, ElementsController::EVENT_DEFINE_EDITOR_CONTENT, $yiiEvent);

            $event->html = $yiiEvent->html;
        });
    }
}
