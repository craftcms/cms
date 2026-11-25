<?php

declare(strict_types=1);

namespace craft\mcp\public;

use Closure;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\mcp\serializers\ElementSerializer as BaseElementSerializer;
use Illuminate\Support\Arr;

/**
 * @since 5.12.0
 */
class ElementSerializer
{
    public function __construct(private readonly BaseElementSerializer $serializer)
    {
    }

    /**
     * @param  list<string>|null  $fields
     * @param  Closure(ElementInterface, list<string>, bool): ?array<string, mixed>|null  $serializeNested
     * @param list<string> $nestedPaths
     * @return array<string, mixed>
     */
    public function serialize(ElementInterface $element, ?array $fields = [], ?Closure $serializeNested = null, array $nestedPaths = []): array
    {
        $data = $element instanceof User
            ? $this->serializeUser($element)
            : null;

        return $this->serializer->serialize($element, $data, public: true, fields: $fields, serializeNested: $serializeNested, nestedPaths: $nestedPaths);
    }

    /**
     * Returns a summary of an element.
     *
     * @param ElementInterface $element
     * @return array<string, mixed>
     */
    public function summarize(ElementInterface $element): array
    {
        return $this->serializer->summarize($element);
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
                ? Craft::$app->getSites()->getSiteById($user->affiliatedSiteId, true)?->handle
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
