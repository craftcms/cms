<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Str;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

/** @internal */
readonly class ParseRefsAction
{
    public function __construct(
        private Elements $elements,
        private Sites $sites,
    ) {}

    /**
     * Parses a string for element [reference tags](https://craftcms.com/docs/5.x/system/reference-tags.html).
     *
     * @param  string  $str  The string to parse
     * @param  int|null  $defaultSiteId  The default site ID to query the elements in
     * @return string The parsed string
     */
    public function handle(string $str, ?int $defaultSiteId = null): string
    {
        if (! str_contains($str, '{')) {
            return $str;
        }

        // First catalog all of the ref tags by element type, ref type ('id' or 'ref'), and ref name,
        // and replace them with placeholder tokens
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
            function (array $matches) use (
                $defaultSiteId,
                &$allRefTagTokens
            ) {
                $fullMatch = $matches[0];
                $elementType = $matches['elementType'];
                $ref = $matches['ref'];
                $siteId = $matches['site'] ?? null;
                $attribute = $matches['attr'] ?? null;
                $fallback = $matches['fallback'] ?? $fullMatch;

                // Swap out the ref handle for the element type
                $elementType = $this->elements->getElementTypeByRefHandle($elementType);

                // Use the fallback if we couldn't find an element type
                if ($elementType === null) {
                    return $fallback;
                }

                // Get the site
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
            // No ref tags
            return $str;
        }

        // Now swap them with the resolved values
        $search = [];
        $replace = [];

        foreach ($allRefTagTokens as $siteId => $siteTokens) {
            foreach ($siteTokens as $elementType => $tokensByType) {
                foreach ($tokensByType as $refType => $tokensByName) {
                    // Get the elements, indexed by their ref value
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

                        // if the reference contains a slash (e.g. section/slug),
                        // also index it by just whatever comes after it
                        if ($refType === 'ref' && ($slash = strrpos((string) $ref, '/')) !== false) {
                            $elements[substr((string) $ref, $slash + 1)] ??= $element;
                        }
                    }

                    // Now append new token search/replace strings
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

        // Swap the tokens with the references
        return str_replace($search, $replace, $str);
    }

    /**
     * Returns the replacement for a given reference tag.
     */
    private function getRefTokenReplacement(
        ?ElementInterface $element,
        ?string $attribute,
        string $fallback,
        string $fullMatch,
    ): string {
        if ($element === null) {
            // Put the ref tag back
            return $fallback;
        }

        if (empty($attribute) || ! isset($element->$attribute)) {
            // Default to the URL
            return (string) $element->getUrl();
        }

        try {
            $value = $element->$attribute;

            if (is_object($value) && ! method_exists($value, '__toString')) {
                throw new Exception('Object of class '.$value::class.' could not be converted to string');
            }

            return $this->handle((string) $value);
        } catch (Throwable $e) {
            // Log it
            Log::error("An exception was thrown when parsing the ref tag \"$fullMatch\":\n".$e->getMessage(), [__METHOD__]);

            // Replace the token with the default value
            return $fallback;
        }
    }
}
