<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Operations;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Str;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;
use Throwable;

/** @internal */
#[Singleton]
readonly class ElementRefs
{
    public function __construct(
        private Elements $elements,
        private Sites $sites,
    ) {}

    public function parseRefs(string $str, ?int $defaultSiteId = null): string
    {
        if (! str_contains($str, '{')) {
            return $str;
        }

        $allRefTagTokens = [];
        $str = preg_replace_callback(
            Elements::REF_TAG_PATTERN,
            function (array $matches) use ($defaultSiteId, &$allRefTagTokens) {
                $fullMatch = $matches[0];
                $elementType = $matches['elementType'];
                $ref = $matches['ref'];
                $siteId = $matches['site'] ?? null;
                $attribute = $matches['attr'] ?? null;
                $fallback = $matches['fallback'] ?? $fullMatch;

                $elementType = $this->elements->getElementTypeByRefHandle($elementType);

                if ($elementType === null) {
                    return $fallback;
                }

                $siteId = $this->siteIdForReference($siteId, $defaultSiteId);

                if ($siteId === false) {
                    return $fallback;
                }

                $refType = is_numeric($ref) ? 'id' : 'ref';
                $token = '{'.Str::random(9).'}';
                $allRefTagTokens[$siteId][$elementType][$refType][$ref][] = [$token, $attribute, $fallback, $fullMatch];

                return $token;
            },
            $str,
            -1,
            $count,
        );

        if ($count === 0) {
            return $str;
        }

        $search = [];
        $replace = [];

        foreach ($allRefTagTokens as $siteId => $siteTokens) {
            foreach ($siteTokens as $elementType => $tokensByType) {
                foreach ($tokensByType as $refType => $tokensByName) {
                    $refNames = array_keys($tokensByName);
                    $elementQuery = $this->elements->createElementQuery($elementType)
                        ->siteId($siteId)
                        ->status(null);

                    if ($refType === 'id') {
                        $elementQuery->id($refNames);
                    } elseif (method_exists($elementQuery, 'ref')) {
                        $elementQuery->ref($refNames);
                    }

                    $elements = [];
                    foreach ($elementQuery->all() as $element) {
                        $ref = $refType === 'id' ? $element->id : $element->getRef();
                        $elements[$ref] = $element;

                        if ($refType === 'ref' && ($slash = strrpos((string) $ref, '/')) !== false) {
                            $elements[substr((string) $ref, $slash + 1)] ??= $element;
                        }
                    }

                    foreach ($tokensByName as $refName => $tokens) {
                        $element = $elements[$refName] ?? null;

                        foreach ($tokens as [$token, $attribute, $fallback, $fullMatch]) {
                            $search[] = $token;
                            $replace[] = $this->getRefTokenReplacement($element, $attribute, $fallback, $fullMatch);
                        }
                    }
                }
            }
        }

        return str_replace($search, $replace, $str);
    }

    /**
     * @return int[]
     */
    public function targetIds(string $str, ?int $defaultSiteId = null): array
    {
        if (! str_contains($str, '{')) {
            return [];
        }

        preg_match_all(Elements::REF_TAG_PATTERN, $str, $matches, PREG_SET_ORDER);

        $targetIds = [];
        $resolvedRefs = [];
        $numericRefs = [];

        foreach ($matches as $match) {
            if (ctype_digit($match['ref'])) {
                $elementType = $this->elements->getElementTypeByRefHandle($match['elementType']);
                $siteId = $this->siteIdForReference($match['site'] ?? null, $defaultSiteId);

                if ($elementType !== null && $siteId !== false) {
                    $numericRefs[$siteId ?? '*'][$elementType][(int) $match['ref']] = true;
                }

                continue;
            }

            $targetId = $this->targetIdForRefTag($match, $defaultSiteId, $resolvedRefs);

            if ($targetId !== null) {
                $targetIds[$targetId] = true;
            }
        }

        foreach ($numericRefs as $siteId => $refsByType) {
            foreach ($refsByType as $elementType => $refs) {
                $query = $this->elements->createElementQuery($elementType)
                    ->siteId($siteId === '*' ? null : $siteId)
                    ->status(null)
                    ->id(array_keys($refs));

                foreach ($query->all() as $element) {
                    $targetIds[$element->id] = true;
                }
            }
        }

        return array_keys($targetIds);
    }

    /**
     * @param  int[]  $oldTargetIds
     */
    public function replaceTargetRefs(string $str, array $oldTargetIds, int $newTargetId, ?int $defaultSiteId = null): string
    {
        $oldTargetIds = array_flip(array_map(intval(...), $oldTargetIds));

        return preg_replace_callback(Elements::REF_TAG_PATTERN, function (array $matches) use ($oldTargetIds, $newTargetId, $defaultSiteId) {
            $targetId = $this->targetIdForRefTag($matches, $defaultSiteId);

            if ($targetId === null || ! isset($oldTargetIds[$targetId])) {
                return $matches[0];
            }

            return substr_replace(
                $matches[0],
                (string) $newTargetId,
                strlen($matches['elementType']) + 2,
                strlen($matches['ref']),
            );
        }, $str) ?? $str;
    }

    /**
     * @param  array<string|int, string>  $matches
     * @param  array<string, int|null>  $resolvedRefs
     */
    private function targetIdForRefTag(array $matches, ?int $defaultSiteId, array &$resolvedRefs = []): ?int
    {
        $elementType = $this->elements->getElementTypeByRefHandle($matches['elementType']);

        if ($elementType === null) {
            return null;
        }

        $siteId = $this->siteIdForReference($matches['site'] ?? null, $defaultSiteId);

        if ($siteId === false) {
            return null;
        }

        $ref = $matches['ref'];

        $cacheKey = sprintf('%s:%s:%s', $elementType, $siteId ?? '*', $ref);

        if (! ctype_digit((string) $ref)) {

            if (isset($resolvedRefs[$cacheKey])) {
                return $resolvedRefs[$cacheKey];
            }
        }

        $query = $this->elements->createElementQuery($elementType)
            ->siteId($siteId)
            ->status(null);

        if (ctype_digit((string) $ref)) {
            return ($query->id((int) $ref)->all()[0] ?? null)?->id;
        }

        if (method_exists($query, 'ref')) {
            $query->ref($ref);
        }

        foreach ($query->all() as $element) {
            $elementRef = $element->getRef();

            if ($elementRef === null) {
                continue;
            }

            if ($elementRef === $ref) {
                return $resolvedRefs[$cacheKey] = $element->id;
            }

            if (($slash = strrpos($elementRef, '/')) !== false && substr($elementRef, $slash + 1) === $ref) {
                return $resolvedRefs[$cacheKey] = $element->id;
            }
        }

        return null;
    }

    private function siteIdForReference(?string $site, ?int $defaultSiteId): int|false|null
    {
        if ($site === null || $site === '') {
            return $defaultSiteId;
        }

        if (is_numeric($site)) {
            return (int) $site;
        }

        try {
            $site = Str::isUuid($site)
                ? $this->sites->getSiteByUid($site)
                : $this->sites->getSiteByHandle($site);
        } catch (SiteNotFoundException) {
            return false;
        }

        return $site === null ? false : $site->id;
    }

    private function getRefTokenReplacement(
        ?ElementInterface $element,
        ?string $attribute,
        string $fallback,
        string $fullMatch,
    ): string {
        if ($element === null) {
            return $fallback;
        }

        if (empty($attribute) || ! isset($element->$attribute)) {
            return (string) $element->getUrl();
        }

        try {
            $value = $element->$attribute;

            if (is_object($value) && ! method_exists($value, '__toString')) {
                throw new Exception('Object of class '.$value::class.' could not be converted to string');
            }

            return $this->parseRefs((string) $value);
        } catch (Throwable $throwable) {
            Log::error("An exception was thrown when parsing the ref tag \"$fullMatch\":\n".$throwable->getMessage(), [__METHOD__]);

            return $fallback;
        }
    }
}
