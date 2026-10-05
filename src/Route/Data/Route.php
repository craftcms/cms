<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route\Data;

use CraftCms\Cms\Route\Exceptions\InvalidRouteException;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Collection;
use Inertia\PropertyContext;
use Inertia\ProvidesInertiaProperty;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Route implements ProvidesInertiaProperty
{
    /**
     * @param  list<string|array{0: string, 1: string}>  $uriParts
     */
    public function __construct(
        /**
         * @var list<string|array{0: string, 1: string}> $uriParts The URI as defined by the user. This is an array where each element is either a
         *                                               string or an array containing the name of a subpattern and the subpattern
         */
        public array $uriParts,

        /**
         * @var string $template The template to route matching requests to
         */
        public string $template,

        /**
         * @var string|null The site UID the route should be limited to, if any
         */
        public ?string $siteUid = null,

        /**
         * @var string $uid The route UID.
         */
        public ?string $uid = null,

        public ?int $sortOrder = null,
    ) {}

    public function normalize(): void
    {
        if (! array_is_list($this->uriParts)) {
            $this->throwInvalidUriParts();
        }

        $normalizedParts = [];

        foreach ($this->uriParts as $part) {
            if (is_string($part)) {
                if ($part !== '') {
                    $normalizedParts[] = $part;
                }

                continue;
            }

            if (
                ! is_array($part) ||
                ! array_is_list($part) ||
                count($part) !== 2 ||
                ! is_string($part[0]) ||
                ! is_string($part[1])
            ) {
                $this->throwInvalidUriParts();
            }

            $normalizedParts[] = [$part[0], $part[1]];
        }

        $this->uriParts = $normalizedParts;
    }

    /**
     * @return array{template: string, uriParts: list<string|array{0: string, 1: string}>, siteUid: string|null}
     */
    public function configData(): array
    {
        return [
            'template' => $this->template,
            'uriParts' => $this->uriParts,
            'siteUid' => $this->siteUid,
        ];
    }

    public function getUri(): string
    {
        return collect($this->uriParts)->map(function (string|array|null $part) {
            if (is_string($part)) {
                return $part;
            }

            return "{{$part[0]}}";
        })->implode('');
    }

    public function uriDisplayHtml(): string
    {
        if (empty($this->uriParts)) {
            return '';
        }

        $uriDisplayHtml = '';

        foreach ($this->uriParts as $part) {
            if (is_string($part)) {
                $uriDisplayHtml .= Html::encode($part);

                continue;
            }

            $uriDisplayHtml .= Html::encodeParams(
                '<span class="token" data-name="{name}" data-value="{value}"><span>{name}</span></span>',
                [
                    'name' => $part[0],
                    'value' => $part[1],
                ],
            );
        }

        return $uriDisplayHtml;
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertiaProperty(PropertyContext $prop): array
    {
        /** @var Collection<string, Site> $sitesByUid */
        $sitesByUid = Sites::getAllSites()->keyBy('uid');

        return [
            'uid' => $this->uid,
            'siteUid' => $this->siteUid,
            'siteName' => $this->siteUid
                ? t($sitesByUid->get($this->siteUid)?->getName() ?? $this->siteUid, category: 'site')
                : t('Global'),
            'uriParts' => array_values($this->uriParts) ?: [''],
            'uriDisplayHtml' => $this->uriDisplayHtml(),
            'template' => $this->template,
            'sortOrder' => $this->sortOrder,
        ];
    }

    private function throwInvalidUriParts(): never
    {
        throw new InvalidRouteException($this, [
            'uriParts' => [t('Invalid route URI.')],
        ]);
    }
}
