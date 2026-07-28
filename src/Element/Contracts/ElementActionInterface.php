<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
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
