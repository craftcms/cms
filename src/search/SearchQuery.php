<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\search;

use craft\helpers\StringHelper;

/**
 * Search Query class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class SearchQuery
{
    /**
     * @var string
     */
    private string $_query;

    /**
     * @var array
     * @phpstan-var array{subLeft:bool,subRight:bool,exclude:bool,exact:bool}
     */
    private array $_defaultTermOptions;

    /**
     * @var SearchQueryTerm[]|SearchQueryTermGroup[]
     */
    private array $_tokens = [];

    /**
     * Constructor
     *
     * @param string $query
     * @param array $defaultTermOptions
     * @phpstan-param array{subLeft?:bool,subRight?:bool,exclude?:bool,exact?:bool} $defaultTermOptions
     */
    public function __construct(string $query, array $defaultTermOptions = [])
    {
        $this->_query = $query;
        $this->_defaultTermOptions = $defaultTermOptions + [
                'subLeft' => false,
                'subRight' => true,
                'exclude' => false,
                'exact' => false,
            ];

        $this->_parse();
    }

    /**
     * Returns the tokens.
     *
     * @return SearchQueryTerm[]|SearchQueryTermGroup[]
     */
    public function getTokens(): array
    {
        return $this->_tokens;
    }

    /**
     * Returns the given query.
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->_query;
    }

    /**
     * Parses the query into an array of tokens.
     */
    private function _parse(): void
    {
        for ($token = strtok($this->_query, ' '); $token !== false; $token = strtok(' ')) {
            $appendToPrevious = false;

            if ($token === 'OR') {
                // Grab the next one or bail
                if (($token = strtok(' ')) === false) {
                    break;
                }

                $totalTokens = count($this->_tokens);

                // I suppose it’s possible the query started with "OR"
                if ($totalTokens) {
                    // Set the previous token to a TermGroup, if it’s not already
                    $previousToken = $this->_tokens[$totalTokens - 1];

                    if (!$previousToken instanceof SearchQueryTermGroup) {
                        $previousToken = new SearchQueryTermGroup([$previousToken]);
                        $this->_tokens[$totalTokens - 1] = $previousToken;
                    }

                    $appendToPrevious = true;
                }
            }

            // Instantiate the term
            $term = new SearchQueryTerm();

            // Is this an exclude term?
            if (StringHelper::first($token, 1) === '-') {
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
                if (StringHelper::last($token, 1) === StringHelper::first($token, 1)) {
                    $token = mb_substr($token, 1, -1);
                } else {
                    $token = mb_substr($token, 1) . ' ' . strtok(StringHelper::first($token, 1));
                }

                $term->phrase = true;
            }

            // Include sub-word matches?
            if ($token && StringHelper::first($token, 1) === '*') {
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
                $term->subLeft = $term->subLeft ?? false;
                $term->subRight = $term->subRight ?? false;
            }

            // Now apply the default options
            foreach ($this->_defaultTermOptions as $name => $value) {
                if (!isset($term->$name)) {
                    $term->$name = $value;
                }
            }

            $term->term = $token;

            if ($appendToPrevious) {
                /** @noinspection PhpUndefinedVariableInspection */
                /** @phpstan-ignore-next-line */
                $previousToken->terms[] = $term;
            } else {
                $this->_tokens[] = $term;
            }
        }
    }
}
