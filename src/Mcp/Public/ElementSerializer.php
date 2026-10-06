<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Public;

use Closure;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\ElementSerializer as BaseElementSerializer;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;

/**
 * @since 6.0.0
 */
class ElementSerializer
{
    public function __construct(private readonly BaseElementSerializer $serializer) {}

    /**
     * @param  list<string>|null  $fields
     * @param  Closure(ElementInterface): ?array<string, mixed>|null  $serializeNested
     * @return array<string, mixed>
     */
    public function serialize(ElementInterface $element, ?array $fields = [], ?Closure $serializeNested = null): array
    {
        $data = $element instanceof User
            ? $this->serializeUser($element)
            : null;

        return $this->serializer->serialize($element, $data, public: true, fields: $fields, serializeNested: $serializeNested);
    }

    /** @return array<string, mixed> */
    private function serializeUser(User $user): array
    {
        return Arr::whereNotNull([
            'id' => $user->id,
            'uid' => $user->uid,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->getName(),
            'friendlyName' => $user->getFriendlyName(),
            'fullName' => $user->fullName,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'affiliatedSiteId' => $user->affiliatedSiteId,
            'affiliatedSiteHandle' => $user->affiliatedSiteId
                ? Sites::getSiteById($user->affiliatedSiteId, true)?->handle
                : null,
            'dateCreated' => $this->date($user->dateCreated),
            'dateUpdated' => $this->date($user->dateUpdated),
        ]);
    }

    private function date(mixed $date): ?string
    {
        $formatted = DateTimeHelper::toIso8601($date);

        return $formatted !== false ? $formatted : null;
    }
}
