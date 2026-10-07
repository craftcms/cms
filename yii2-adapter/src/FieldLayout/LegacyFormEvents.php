<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\FieldLayout;

use craft\base\Event as YiiEvent;
use craft\events\CreateFieldLayoutFormEvent;
use craft\models\FieldLayout as LegacyFieldLayout;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Tab;
use CraftCms\Cms\Ui\UiContext;
use Illuminate\Container\Attributes\Singleton;
use RuntimeException;
use WeakMap;

use function CraftCms\Cms\t;

#[Singleton]
class LegacyFormEvents
{
    /** @var WeakMap<UiContext, array<int, CreateFieldLayoutFormEvent>> */
    private WeakMap $events;

    public function __construct()
    {
        $this->events = new WeakMap();
    }

    public function prepare(FieldLayout $layout, ?ElementInterface $element, UiContext $context): ?CreateFieldLayoutFormEvent
    {
        if (!YiiEvent::hasHandlers(LegacyFieldLayout::class, LegacyFieldLayout::EVENT_CREATE_FORM)) {
            return null;
        }

        $key = spl_object_id($layout);

        if (isset($this->events[$context][$key])) {
            return $this->events[$context][$key];
        }

        $form = new FieldLayoutForm();
        $event = new CreateFieldLayoutFormEvent([
            'form' => $form,
            'element' => $element,
            'static' => $context->mode !== ControlMode::Editable,
            'tabs' => $layout->getTabs(),
        ]);
        $event->sender = $layout;

        YiiEvent::trigger(LegacyFieldLayout::class, LegacyFieldLayout::EVENT_CREATE_FORM, $event);

        $mutations = array_filter([
            $event->form !== $form ? 'form' : null,
            $form->tabs !== [] ? 'form.tabs' : null,
            $form->tabIdPrefix !== null ? 'form.tabIdPrefix' : null,
            $form->errorKeyPrefix !== null ? 'form.errorKeyPrefix' : null,
        ]);

        if ($mutations !== []) {
            throw new RuntimeException(sprintf(
                'Legacy FieldLayout event mutation [%s] is incompatible with UI rendering.',
                implode(', ', $mutations),
            ));
        }

        $events = $this->events[$context] ?? [];
        $events[$key] = $event;
        $this->events[$context] = $events;

        return $event;
    }

    public function forget(FieldLayout $layout, UiContext $context): void
    {
        $events = $this->events[$context] ?? [];
        unset($events[spl_object_id($layout)]);
        $this->events[$context] = $events;
    }

    public function compileTab(
        FieldLayout $layout,
        FieldLayoutTab $tab,
        ?ElementInterface $element,
        UiContext $context,
        string $fallbackUid,
    ): ?Node {
        $tab->setLayout($layout);

        if (!$tab->showInForm($element)) {
            return null;
        }

        $nodes = [];

        foreach ($tab->getElements() as $layoutElement) {
            if (!$layoutElement->showInForm($element)) {
                continue;
            }

            $node = $layoutElement->uiNode(new FieldLayoutElementContext(
                $element,
                $context,
                $layoutElement->uiMode($element),
            ));

            if ($node !== null) {
                $nodes[] = $node;
            }
        }

        if ($nodes === []) {
            return null;
        }

        return Tab::make(
            $tab->uid ?? $fallbackUid,
            t($tab->name ?? '', category: 'site'),
            $nodes,
        );
    }
}
