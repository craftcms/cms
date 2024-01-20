<?php

namespace craft\ui\components;

use Craft;
use craft\enums\MenuItemType;
use craft\helpers\Json;

class MenuItemButton extends MenuItem
{
    protected ?string $action = null;

    protected ?string $redirect = null;

    protected ?array $params = null;

    protected ?string $form = null;

    protected MenuItemType $type = MenuItemType::Button;

    public function action(?string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function redirect(?string $value): static
    {
        $this->redirect = $value;
        return  $this;
    }

    public function params(array $params = []): static
    {
        $this->params = $params;
        return $this;
    }

    public function form(?string $form): static
    {
        $this->form = $form;
        return $this;
    }

    public function getAttributes(): array
    {
        $security = Craft::$app->getSecurity();

        return array_merge_recursive(parent::getAttributes(), [
            'class' => [
                $this->getAction() ? 'formsubmit' : null,
                $this->getDestructive() ? 'error' : null,
            ],
            'data' => [
                'action' => $this->getAction(),
                'params' => Json::encode($this->getParams()),
                'redirect' => $security->hashData($this->getRedirect()),
                'form' => $this->getForm() ? 'true' : 'false',
            ],
        ]);
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getRedirect(): ?string
    {
        return $this->redirect;
    }

    public function getForm(): ?string
    {
        return $this->form;
    }
}
