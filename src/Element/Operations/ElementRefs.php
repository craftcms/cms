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
            '/
                \{                                      # Tags always begin with a {
                    (?P<elementType>[\w\\\\]+)          # Ref handle or element type class
                    \:(?P<ref>[^@\:\}\|]+)              # Identifier (ID, or another format supported by the element type)
                    (?:@(?P<site>[^\:\}\|]+))?          # [Optional] Site handle, ID, or UUID
                    (?:\:(?P<attr>[^\}\| ]+))?          # [Optional] Attribute, property, or field
                    (?:\ *\|\|\ *(?P<fallback>[^\}]+))? # [Optional] Fallback text (if the ref fails to resolve)
                \}                                      # Tags always close with a }
            /x',
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

                if (! empty($siteId)) {
                    if (is_numeric($siteId)) {
                        $siteId = (int) $siteId;
                    } else {
                        try {
                            $site = Str::isUuid($siteId)
                                ? $this->sites->getSiteByUid($siteId)
                                : $this->sites->getSiteByHandle($siteId);
                        } catch (SiteNotFoundException) {
                            $site = null;
                        }

                        if (! $site) {
                            return $fallback;
                        }

                        $siteId = $site->id;
                    }
                } else {
                    $siteId = $defaultSiteId;
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
