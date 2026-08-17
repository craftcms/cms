<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\ConditionBuilder;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Form\Nodes\Separator;
use CraftCms\Cms\Support\Facades\Conditions;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Elements\User;
use Override;

use function CraftCms\Cms\currentUserElement;
use function CraftCms\Cms\t;

/**
 * @property ?ElementConditionInterface $elementCondition The element condition for this layout element
 * @property ?UserCondition $userCondition The user condition for this layout element
 * @property FieldLayout $layout The layout this element belongs to
 */
abstract class FieldLayoutComponent extends Component
{
    private static UserCondition $defaultUserCondition;

    /**
     * @var ElementConditionInterface[]
     */
    private static array $defaultElementConditions = [];

    /**
     * @var string|null The UUID of the layout element.
     */
    public ?string $uid = null;

    /**
     * @var string|null The element type the field layout is for
     */
    public ?string $elementType = null;

    /**
     * @var FieldLayout The field layout tab this element belongs to
     *
     * @see getLayout()
     * @see setLayout()
     */
    private ?FieldLayout $_layout = null;

    /**
     * @var UserCondition|class-string<UserCondition>|array|null
     *
     * @phpstan-var UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null
     *
     * @see getUserCondition()
     * @see setUserCondition()
     */
    private mixed $_userCondition = null;

    /**
     * @var ElementConditionInterface|class-string<ElementConditionInterface>|array|null
     *
     * @phpstan-var ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null
     *
     * @see getElementCondition()
     * @see setElementCondition()
     */
    private mixed $_elementCondition = null;

    private static function defaultUserCondition(): UserCondition
    {
        return self::$defaultUserCondition ??= User::createCondition();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    private static function defaultElementCondition(string $elementType): ElementConditionInterface
    {
        return self::$defaultElementConditions[$elementType] ??= $elementType::createCondition();
    }

    public function getLayout(): ?FieldLayout
    {
        return $this->_layout;
    }

    public function setLayout(FieldLayout $layout): void
    {
        $this->_layout = $layout;
    }

    protected function conditional(): bool
    {
        return true;
    }

    public function hasConditions(): bool
    {
        if ($this->getUserCondition()) {
            return true;
        }

        return (bool) $this->getElementCondition();
    }

    public function getUserCondition(): ?UserCondition
    {
        if (isset($this->_userCondition) && ! $this->_userCondition instanceof UserCondition) {
            $this->_userCondition = $this->normalizeCondition($this->_userCondition);
        }

        return $this->_userCondition;
    }

    /**
     * Sets the user condition for this layout element.
     *
     * @param  UserCondition|class-string<UserCondition>|array{class:class-string<UserCondition>}|null  $userCondition
     */
    public function setUserCondition(mixed $userCondition): void
    {
        $this->_userCondition = $userCondition;
    }

    public function userCondition(mixed $userCondition): static
    {
        $this->setUserCondition($userCondition);

        return $this;
    }

    public function getElementCondition(): ?ElementConditionInterface
    {
        if (isset($this->_elementCondition) && ! $this->_elementCondition instanceof ElementConditionInterface) {
            if (is_string($this->_elementCondition)) {
                $this->_elementCondition = ['class' => $this->_elementCondition];
            }

            $this->_elementCondition = array_merge(
                ['fieldLayouts' => [$this->getLayout()]],
                $this->_elementCondition,
            );

            $this->_elementCondition = $this->normalizeCondition($this->_elementCondition);
        }

        return $this->_elementCondition;
    }

    /**
     * Sets the element condition for this layout element.
     *
     * @param  ElementConditionInterface|class-string<ElementConditionInterface>|array{class:class-string<ElementConditionInterface>}|null  $elementCondition
     */
    public function setElementCondition(mixed $elementCondition): void
    {
        $this->_elementCondition = $elementCondition;
    }

    public function elementCondition(mixed $elementCondition): static
    {
        $this->setElementCondition($elementCondition);

        return $this;
    }

    /**
     * Normalizes a condition.
     *
     * @template T of ConditionInterface
     *
     * @param  ConditionInterface|class-string<T>|array{class:class-string<T>}|null  $condition
     * @return T|null
     */
    protected function normalizeCondition(mixed $condition): ?ConditionInterface
    {
        // An empty array is how a ConditionBuilder Control represents “no
        // condition”, and is what it posts back when nothing has been set.
        // getElementCondition() may have merged `fieldLayouts` in by then, so
        // treat any classless config as “no condition” rather than failing.
        if ($condition === null || (is_array($condition) && ! isset($condition['class']))) {
            return null;
        }

        if (! $condition instanceof ConditionInterface) {
            $condition = Conditions::createCondition($condition);
        }

        if (! $condition->getConditionRules()) {
            return null;
        }

        return $condition;
    }

    #[Override]
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['elementType']);
        $fields['userCondition'] = fn () => $this->getUserCondition()?->getConfig();
        $fields['elementCondition'] = fn () => $this->getElementCondition()?->getConfig();

        return $fields;
    }

    public function hasSettings(): bool
    {
        return $this->conditional();
    }

    /**
     * Returns the settings Form for the layout component.
     *
     * ::: tip
     * Subclasses should override [[settingsNodes()]] instead of this method.
     * :::
     */
    final public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        $settings = $this->settingsNodes($context);
        $conditions = $this->conditionalSettingsNodes($context);

        $nodes = match (true) {
            $settings === [] => $conditions,
            $conditions === [] => $settings,
            default => [...$settings, Separator::make('settings-conditions'), ...$conditions],
        };

        return $nodes === [] ? null : Form::make($nodes);
    }

    /** @return list<Node> */
    protected function settingsNodes(FormContext $context): array
    {
        return [];
    }

    /** @return list<Node> */
    protected function conditionalSettingsNodes(FormContext $context): array
    {
        if (! $this->conditional()) {
            return [];
        }

        return [
            $this->conditionGroupNode(
                'visibility-conditions',
                t('Visibility Conditions'),
                'userCondition',
                t('Only show for users who match the following rules:'),
                $this->getUserCondition(),
                'elementCondition',
                'Only show when editing {type} that match the following rules:',
                $this->getElementCondition(),
            ),
        ];
    }

    /**
     * Builds a fieldset of user/element condition builders — the shared shape
     * behind the visibility and editability condition groups.
     */
    protected function conditionGroupNode(
        string $groupUid,
        string $groupLabel,
        string $userPath,
        string $userInstructions,
        ?ConditionInterface $userCondition,
        string $elementPath,
        string $elementInstructionsPattern,
        ?ConditionInterface $elementCondition,
        ?ConditionInterface $defaultUserCondition = null,
        ?string $defaultElementConditionClass = null,
    ): Group {
        $children = [
            Field::make(t('Current User Condition'), ConditionBuilder::make($userPath)
                ->conditionClass(($defaultUserCondition ?? self::defaultUserCondition())::class)
                ->forProjectConfig()
                ->value($userCondition?->getConfig() ?? []))
                ->instructions($userInstructions),
        ];

        // Do we know the element type?
        /** @var class-string<ElementInterface>|string|null $elementType */
        $elementType = $this->elementType ?? $this->getLayout()?->type;

        if ($elementType && is_subclass_of($elementType, ElementInterface::class)) {
            $conditionClass = $defaultElementConditionClass
                ?? self::defaultElementCondition($elementType)::class;

            // getConfig() is null for an empty layout, which has no fields to
            // offer rules for anyway.
            $layoutConfig = $this->getLayout()?->getConfig();

            $children[] = Field::make(
                t('{type} Condition', ['type' => $elementType::displayName()]),
                ConditionBuilder::make($elementPath)
                    ->conditionClass($conditionClass)
                    ->fieldLayouts($layoutConfig === null ? [] : [$layoutConfig])
                    ->forProjectConfig()
                    ->value($elementCondition?->getConfig() ?? []),
            )->instructions(t($elementInstructionsPattern, [
                'type' => $elementType::pluralLowerDisplayName(),
            ]));
        }

        return Group::make($groupUid, $children)->label($groupLabel);
    }

    /**
     * Returns whether the layout element should be shown in an edit form for the given element.
     *
     * This will only be called if the field layout component has been saved with a [[uid|UUID]] already.
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        event($event = new FieldLayoutComponentShowInFormResolving(
            fieldLayoutComponent: $this,
            fieldLayout: $this->getLayout(),
            element: $element,
        ));

        if (! $event->showInForm || $event->handled) {
            return $event->showInForm;
        }

        if (! $this->conditional()) {
            return true;
        }

        $userCondition = $this->getUserCondition();
        $elementCondition = $this->getElementCondition();

        if ($userCondition) {
            $currentUser = currentUserElement();
            if ($currentUser && ! $userCondition->matchElement($currentUser)) {
                return false;
            }
        }

        if ($elementCondition && $element && ! $elementCondition->matchElement($element)) {
            return false;
        }

        return true;
    }
}
