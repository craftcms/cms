<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Public;

use Closure;
use CraftCms\Cms\Element\Attributes\ElementProperty;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer as BaseElementSerializer;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use DateTimeInterface;

/**
 * @since 6.0.0
 */
class ElementSerializer
{
    public function __construct(private readonly BaseElementSerializer $serializer) {}

    /**
     * @param  list<string>|null  $fields
     * @param  Closure(ElementInterface, list<string>, bool): ?array<string, mixed>|null  $serializeNested
     * @param  list<string>  $nestedPaths
     * @return array<string, mixed>
     */
    public function serialize(ElementInterface $element, ?array $fields = [], ?Closure $serializeNested = null, array $nestedPaths = []): array
    {
        $data = ['type' => $element::class, 'id' => $element->id, 'uid' => $element->uid];

        foreach (array_keys(ElementProperty::readable($element::class)) as $name) {
            $value = $element->$name;
            $data[$name] = $value instanceof DateTimeInterface ? $this->date($value) : $value;
        }

        if ($element instanceof User && $element->affiliatedSiteId) {
            $data['affiliatedSiteHandle'] = Sites::getSiteById($element->affiliatedSiteId, true)?->handle;
        }

        return $this->serializer->serialize($element, $data, public: true, fields: $fields, serializeNested: $serializeNested, nestedPaths: $nestedPaths);
    }

    /**
     * Returns a summary of an element.
     *
     * @return array<string, mixed>
     */
    public function summarize(ElementInterface $element): array
    {
        return $this->serializer->summarize($element);
    }

    private function date(mixed $date): ?string
    {
        $formatted = DateTimeHelper::toIso8601($date);

        return $formatted !== false ? $formatted : null;
    }
}
