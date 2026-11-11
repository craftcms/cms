<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Element;

use craft\base\ElementAction as LegacyElementAction;
use CraftCms\Cms\Asset\Actions\DeleteAssets;
use CraftCms\Cms\Element\Actions\Delete;
use CraftCms\Cms\Element\Actions\DeleteForSite;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Actions\Restore;
use CraftCms\Cms\Element\Actions\SetStatus;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\User\Actions\SuspendUsers;
use CraftCms\Cms\User\Actions\UnsuspendUsers;
use Override;
use ReflectionMethod;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\template;

/**
 * Serializes element actions for the legacy element index, with the trigger
 * markup and behavior it renders each action with.
 *
 * Actions that define `getTriggerHtml()` supply their own trigger. Built-in
 * actions get a `legacyTrigger` descriptor, which the legacy index turns into
 * a `Craft.ElementActionTrigger`.
 *
 * @internal
 */
class LegacyElementActions extends ElementActions
{
    #[Override]
    public function serializeActions(iterable $actions): array
    {
        $data = [];
        $triggerIdCounts = [];

        foreach ($actions as $action) {
            if (!$action instanceof ElementActionInterface) {
                continue;
            }

            $config = $this->actionConfig($action);

            // Built-in triggers are found by ID, and an action can be listed twice (e.g. Delete with and without descendants)
            if (isset($config['legacyTrigger'])) {
                $count = $triggerIdCounts[$config['triggerId']] = ($triggerIdCounts[$config['triggerId']] ?? 0) + 1;

                if ($count > 1) {
                    $config['triggerId'] .= "-$count";
                }
            }

            $data[] = $config;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function actionConfig(ElementActionInterface $action): array
    {
        $config = ElementHelper::actionConfig($action);
        $config['triggerId'] = method_exists($action, 'getTriggerId')
            ? $action->getTriggerId()
            : sprintf('%s-actiontrigger', $action::class);

        if ($this->definesOwnTrigger($action)) {
            /** @phpstan-ignore method.notFound */
            $config['trigger'] = $action->getTriggerHtml();

            return $config;
        }

        $config['trigger'] = $this->triggerHtml($action);
        $config['legacyTrigger'] = $this->legacyTrigger($action);

        return $config;
    }

    private function definesOwnTrigger(ElementActionInterface $action): bool
    {
        if (!method_exists($action, 'getTriggerHtml')) {
            return false;
        }

        return new ReflectionMethod($action, 'getTriggerHtml')->getDeclaringClass()->getName() !== LegacyElementAction::class;
    }

    private function triggerHtml(ElementActionInterface $action): ?string
    {
        return match (true) {
            $action instanceof SetStatus => template('yii2-adapter/_components/elementactions/SetStatus/trigger'),
            $action instanceof Restore => '<div class="btn formsubmit">' . $action->getTriggerLabel() . '</div>',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function legacyTrigger(ElementActionInterface $action): ?array
    {
        return match (true) {
            $action instanceof DeleteAssets => [
                'handler' => 'deleteAssets',
                'hardDelete' => $action->hard,
                'confirmationMessage' => $action->confirmationMessage,
            ],
            $action instanceof Delete => [
                'handler' => 'delete',
                'withDescendants' => $action->withDescendants,
                'hardDelete' => $action->hard,
                'confirmationMessage' => $action->confirmationMessage,
            ],
            $action instanceof Duplicate => [
                'handler' => 'duplicate',
                'attribute' => $action->asDrafts ? 'data-duplicatable-as-draft' : 'data-duplicatable',
            ],
            $action instanceof DeleteForSite => ['handler' => 'deleteForSite'],
            $action instanceof Restore => [
                'handler' => 'restore',
                'attribute' => $action->restorableElementsOnly ? 'data-restorable' : 'data-savable',
            ],
            $action instanceof SetStatus => ['handler' => 'setStatus'],
            $action instanceof SuspendUsers => [
                'handler' => 'suspendUsers',
                'currentUserId' => currentUser()?->getCraftUserId(),
            ],
            $action instanceof UnsuspendUsers => ['handler' => 'unsuspendUsers'],
            default => null,
        };
    }
}
