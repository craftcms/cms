<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Concerns\ConfigurableComponent;
use CraftCms\Cms\Element\Contracts\ElementActionInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class ElementAction extends Component implements ElementActionInterface
{
    use ConfigurableComponent;

    /**
     * @var class-string<ElementInterface>
     */
    protected string $elementType;

    private ?string $message = null;

    private ?Response $response = null;

    public static function isDestructive(): bool
    {
        return false;
    }

    public static function isDownload(): bool
    {
        return false;
    }

    public function setElementType(string $elementType): void
    {
        $this->elementType = $elementType;
    }

    public function getTriggerLabel(): string
    {
        return static::displayName();
    }

    public function getTriggerHtml(): ?string
    {
        return null;
    }

    public function getConfirmationMessage(): ?string
    {
        return null;
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        return true;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    protected function setMessage(string $message): void
    {
        $this->message = $message;
    }

    protected function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
