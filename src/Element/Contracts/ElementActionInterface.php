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
     * @param  class-string<ElementInterface>  $elementType
     */
    public function setElementType(string $elementType): void;

    public function getTriggerLabel(): string;

    public function getTriggerHtml(): ?string;

    public function getConfirmationMessage(): ?string;

    public function performAction(ElementQueryInterface $query): bool;

    public function getMessage(): ?string;

    public function getResponse(): ?Response;
}
