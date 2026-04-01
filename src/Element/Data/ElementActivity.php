<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use craft\base\ElementInterface;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\User\Elements\User;
use DateTime;

use function CraftCms\Cms\t;

class ElementActivity extends Component
{
    public User $user;

    public ElementInterface $element;

    public ElementActivityType $type;

    public DateTime $timestamp;

    public function toActivityRow(ElementInterface $element): array
    {
        $recordIsCanonical = $this->element->getIsCanonical() || $this->element->isProvisionalDraft;
        $recordIsCanonicalAndPublished = $recordIsCanonical && ! $this->element->getIsUnpublishedDraft();
        $isSameOrUpstream = $element->id === $this->element->id || $recordIsCanonical;

        if ($isSameOrUpstream) {
            $messageParams = [
                'user' => $this->user->getName(),
                'type' => $recordIsCanonicalAndPublished ? $element::lowerDisplayName() : t('draft'),
            ];
            $message = match ($this->type) {
                ElementActivityType::View => t('{user} is viewing this {type}.', $messageParams),
                ElementActivityType::Edit, ElementActivityType::Save => t('{user} is editing this {type}.', $messageParams),
            };
        } else {
            $messageParams = [
                'user' => $this->user->getName(),
                'type' => $element::lowerDisplayName(),
            ];
            $message = match ($this->type) {
                ElementActivityType::View => t('{user} is viewing a draft of this {type}.', $messageParams),
                ElementActivityType::Edit, ElementActivityType::Save => t('{user} is editing a draft of this {type}.', $messageParams),
            };
        }

        return [
            'userId' => $this->user->id,
            'userName' => $this->user->getName(),
            'userThumb' => $this->user->getThumbHtml(26),
            'type' => $this->type->value,
            'message' => $message,
        ];
    }
}
