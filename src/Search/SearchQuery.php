<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search;

use CraftCms\Cms\Support\Str;

final class SearchQuery
{
    /**
     * @var array{subLeft:bool,subRight:bool,exclude:bool,exact:bool}
     */
    private readonly array $defaultTermOptions;

    /**
     * @var array<SearchQueryTerm|SearchQueryTermGroup>
     */
    private array $tokens = [];

    /**
     * @param  array{subLeft?:bool,subRight?:bool,exclude?:bool,exact?:bool}  $defaultTermOptions
     */
    public function __construct(
        private readonly string $query,
        array $defaultTermOptions = [],
    ) {
        $this->defaultTermOptions = $defaultTermOptions + [
            'subLeft' => false,
            'subRight' => true,
            'exclude' => false,
            'exact' => false,
        ];

        $this->parse();
    }

    /**
     * @return array<SearchQueryTerm|SearchQueryTermGroup>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    private function parse(): void
    {
        for ($token = strtok($this->query, ' '); $token !== false; $token = strtok(' ')) {
            $appendToPrevious = false;

            if ($token === 'OR') {
                // Grab the next one or bail
                if (($token = strtok(' ')) === false) {
                    break;
                }

                $totalTokens = count($this->tokens);

                // I suppose it's possible the query started with "OR"
                if ($totalTokens) {
                    // Set the previous token to a TermGroup, if it's not already
                    $previousToken = $this->tokens[$totalTokens - 1];

                    if (! $previousToken instanceof SearchQueryTermGroup) {
                        $previousToken = new SearchQueryTermGroup([$previousToken]);
                        $this->tokens[$totalTokens - 1] = $previousToken;
                    }

                    $appendToPrevious = true;
                }
            }

            // Instantiate the term
            $term = new SearchQueryTerm;

            // Is this an exclude term?
            if (str_starts_with($token, '-')) {
                $term->exclude = true;
                $token = mb_substr($token, 1);
            }

            // Is this an attribute-specific term?
            if (preg_match('/^(\w+)(::?)(.+)$/', $token, $match)) {
                [, $term->attribute, $colons, $token] = $match;
                if ($colons === '::') {
                    $term->exact = true;
                    $term->subLeft = false;
                    $term->subRight = false;
                }
            }

            // Does it start with a quote?
            if ($token && (str_starts_with($token, "'") || str_starts_with($token, '"'))) {
                // Is the end quote at the end of this very token?
                if (Str::take($token, -1) === Str::take($token, 1)) {
                    $token = mb_substr($token, 1, -1);
                } else {
                    $token = mb_substr($token, 1).' '.strtok(Str::take($token, 1));
                }

                $term->phrase = true;
            }

            // Include sub-word matches?
            if ($token && Str::take($token, 1) === '*') {
                $term->subLeft = true;
                $token = mb_substr($token, 1);
            }

            if ($token) {
                if (str_ends_with($token, '*')) {
                    $term->subRight = true;
                    $token = mb_substr($token, 0, -1);
                }
            } else {
                // subRight messes `attr:*` queries up
                $term->subRight = false;
            }

            // If either subLeft or subRight have been enabled, make sure the other is set to false if not also set
            // overriding whatever the default subLeft/subRight term options are.
            // (see https://github.com/craftcms/cms/discussions/10613)
            if ($term->subLeft || $term->subRight) {
                $term->subLeft ??= false;
                $term->subRight ??= false;
            }

            // Now apply the default options
            foreach ($this->defaultTermOptions as $name => $value) {
                if (! isset($term->$name)) {
                    $term->$name = $value;
                }
            }

            $term->term = $token;

            if ($appendToPrevious) {
                /** @phpstan-ignore-next-line */
                $previousToken->terms[] = $term;
            } else {
                $this->tokens[] = $term;
            }
        }
    }
}
