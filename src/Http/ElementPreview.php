<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Crypt;

/**
 * An element's preview targets, and what's needed to load them on the front
 * end: as links that mint a preview token on the way, or as the payload Live
 * Preview loads them in an iframe with.
 *
 * One token is shared by both, so whichever is used first creates it.
 *
 * @since 6.0.0
 */
class ElementPreview
{
    private ?string $token = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $targets = null;

    /** @var array<string, mixed>|null */
    private ?array $tokenParams = null;

    public function __construct(
        private readonly ElementInterface $element,
    ) {}

    /**
     * The element's preview targets, as the element type defines them.
     *
     * @return list<array<string, mixed>>
     */
    public function targets(): array
    {
        if (! $this->element->id) {
            return [];
        }

        return $this->targets ??= $this->element->getPreviewTargets();
    }

    /**
     * The element's preview targets as ready-to-open links.
     *
     * A live element links straight at its URL. Anything not publicly visible
     * yet — a draft, a disabled entry, a future post date — links at
     * `preview/create-token`, which mints a preview token and redirects to the
     * tokenized URL.
     *
     * @return list<array{label: string, url: string}>
     */
    public function links(): array
    {
        $targets = $this->targets();

        if ($targets === []) {
            return [];
        }

        $isLive = $this->isLive();
        $siteToken = $this->siteToken();
        $tokenParams = $isLive ? [] : $this->tokenParams();

        return array_values(array_map(function (array $target) use ($isLive, $siteToken, $tokenParams): array {
            $params = array_filter([
                // Randomized so CDNs don't serve a cached page.
                'x-craft-preview' => $isLive ? null : Crypt::encrypt(Str::random(10)),
                Cms::config()->siteToken => $siteToken,
            ], fn (mixed $value): bool => $value !== null);

            if ($isLive) {
                return [
                    'label' => (string) $target['label'],
                    'url' => Url::url($target['url'], $params),
                ];
            }

            $params[Cms::config()->tokenParam] = $this->token();

            return [
                'label' => (string) $target['label'],
                'url' => Url::actionUrl('preview/create-token', [
                    ...$tokenParams,
                    'redirect' => Url::url($target['url'], $params),
                ]),
            ];
        }, $targets));
    }

    /**
     * What Live Preview needs to load the element's targets in an iframe, or
     * `null` when it has none.
     *
     * The target URLs are untokenized; the client adds the token once it has
     * been created, along with the preview param, which changes on every save
     * so a cached page is never shown.
     *
     * @return array<string, mixed>|null
     */
    public function payload(): ?array
    {
        $targets = $this->targets();

        if ($targets === []) {
            return null;
        }

        $config = Cms::config();

        return [
            'targets' => array_values(array_map(fn (array $target): array => [
                'label' => (string) $target['label'],
                'url' => (string) $target['url'],
                'refresh' => (bool) ($target['refresh'] ?? true),
            ], $targets)),
            'createTokenUrl' => Url::actionUrl('preview/create-token'),
            'tokenParams' => $this->tokenParams(),
            'token' => $this->token(),
            'tokenDuration' => app(RouteTokens::class)->previewTokenDuration(),
            'tokenParam' => $config->tokenParam,
            'paramValue' => Crypt::encrypt(Str::random(10)),
            'siteTokenParam' => $config->siteToken,
            'siteToken' => $this->siteToken(),
            'iframeResizerOptions' => $config->useIframeResizer
                ? (object) $config->previewIframeResizerOptions
                : false,
        ];
    }

    private function isLive(): bool
    {
        $element = $this->element;

        return ($element->getIsCanonical() || $element->isProvisionalDraft) &&
            ! $element->getIsDraft() &&
            $element->enabled &&
            $element->getEnabledForSite() &&
            $element->getRoute() !== null;
    }

    private function siteToken(): ?string
    {
        return once(fn (): ?string => (app()->isDownForMaintenance() || ! $this->element->getSite()->getEnabled())
            ? Crypt::encrypt((string) $this->element->siteId)
            : null);
    }

    private function token(): string
    {
        return $this->token ??= Str::random(32, extendedChars: true);
    }

    /** @return array<string, mixed> */
    private function tokenParams(): array
    {
        $element = $this->element;

        return $this->tokenParams ??= array_filter([
            'elementType' => $element::class,
            'canonicalId' => $element->getCanonicalId(),
            'siteId' => $element->siteId,
            'revisionId' => $element->revisionId,
            'draftId' => $element->isProvisionalDraft ? null : $element->draftId,
            'previewToken' => Crypt::encrypt($this->token()),
        ], fn (mixed $value): bool => $value !== null);
    }
}
