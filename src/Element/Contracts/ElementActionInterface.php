<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Element\ElementActions;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Symfony\Component\HttpFoundation\Response;

interface ElementActionInterface extends ComponentInterface, ConfigurableComponentInterface, Validatable
{
    public static function isDestructive(): bool;

    public static function isDownload(): bool;

    /**
     * Returns whether this action can be performed on more than one selected element at once.
     */
    public static function supportsBulk(): bool;

    /**
     * Returns whether this action can be performed on its elements from a queue job,
     * a chunk of elements at a time.
     *
     * Element indexes perform an action on more than
     * {@see ElementActions::SYNC_LIMIT} elements from a queue
     * job, and only allow actions that return `true` here.
     * An action that returns `true` must not depend on the request, keep state
     * between chunks, or set a response. {@see ElementAction}
     * returns `true` unless the action is a download.
     */
    public function supportsQueue(): bool;

    /**
     * @param  class-string<ElementInterface>  $elementType
     */
    public function setElementType(string $elementType): void;

    /**
     * Returns the ID the trigger element should have.
     *
     * This should be overridden with something unique if the same action can be
     * included multiple times for the same elements.
     *
     * If this is overridden, ensure you configure the `Craft.ElementActionTrigger`
     * JavaScript object with a `triggerId` setting, set to the same value.
     */
    public function getTriggerId(): string;

    public function getTriggerLabel(): string;

    public function getTriggerHtml(): ?string;

    public function getConfirmationMessage(): ?string;

    public function performAction(ElementQueryInterface $query): bool;

    public function getMessage(): ?string;

    public function getResponse(): ?Response;
}
