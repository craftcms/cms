<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use craft\base\Element;
use craft\base\Event;
use craft\events\TemplateEvent;
use craft\web\View;
use yii\base\Event as BaseEvent;
use yii\base\InlineAction;
use yii\debug\Panel;

/**
 * Documentation panel, for displaying contextual
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.4
 */
class DocsPanel extends Panel
{
    /**
     * @var array Element types used in this request.
     */
    private array $_elementTypes = [];

    /**
     * @var string Primary “page template” rendered (if any)
     */
    private string|null $_pageTemplate = null;

    /**
     * @var array Variables passed to the primary "page template"
     */
    private array $_pageVariables = [];

    public function init()
    {
        parent::init();

        // Inspect templates for used tags, variable types, etc.
        Event::on(View::class, View::EVENT_AFTER_RENDER_TEMPLATE, function(TemplateEvent $e) {
            // ...?
        });

        // “Page” variables
        Event::on(View::class, View::EVENT_AFTER_RENDER_PAGE_TEMPLATE, function(TemplateEvent $e) {
            $this->_pageTemplate = $e->template;

            foreach ($e->variables as $key => $val) {
                $this->_pageVariables[$key] = match (gettype($val)) {
                    'object' => get_class($val),
                    default => gettype($val),
                };
            }
        });

        Event::on(Element::class, Element::EVENT_INIT, function(BaseEvent $e) {
            // Do we already know about it?
            if (in_array($e->sender::class, $this->_elementTypes)) {
                return;
            }

            $this->_elementTypes[] = $e->sender::class;
        });
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Docs';
    }

    /**
     * @inheritdoc
     */
    public function getSummary(): string
    {
        return Craft::$app->getView()->render('@app/views/debug/docs/summary', [
            'panel' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getDetail(): string
    {
        $view = Craft::$app->getView();

        return $view->render('@app/views/debug/docs/detail', [
            'panel' => $this,
            'docs' => Craft::$app->getDocs(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function save(): array
    {
        // Request + Routing
        if (Craft::$app->requestedAction instanceof InlineAction) {
            $controller = Craft::$app->requestedAction->controller::class;
            $action = Craft::$app->requestedAction->actionMethod;
        } else {
            // Not likely something we can handle:
            $controller = null;
            $action = null;
        }

        // Elements
        $primaryElement = Craft::$app->getUrlManager()->getMatchedElement();

        return [
            'controller' => $controller,
            'action' => $action,
            'primaryElementId' => $primaryElement->id ?? null,
            'allElementTypes' => $this->_elementTypes,
            'pageTemplate' => $this->_pageTemplate,
            'pageVariables' => $this->_pageVariables,
        ];
    }

    /**
     * @inheritdoc
     */
    public function load($data)
    {
        if ($data['primaryElementId']) {
            $data['primaryElement'] = Craft::$app->getElements()->getElementById($data['primaryElementId']);
        }

        $this->data = $data;
    }
}
