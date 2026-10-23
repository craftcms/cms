<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

use CraftCms\Cms\Ai\AskAi\Data\Finding;
use CraftCms\Cms\Support\Json;

/**
 * A model’s response to an editor’s prompt: the values it changed, and what it has to say about the content.
 *
 * A review can come back with feedback and no values at all.
 *
 * @since 6.0.0
 */
readonly class Answer
{
    /**
     * @param  array<string, mixed>  $values  The values the model changed, indexed by the fields’ keys in prompts and responses, as {@see Target::id()} returns them.
     * @param  string|null  $summary  What the model changed, or its review when it changed nothing.
     * @param  list<Finding>  $findings  Specific problems the model noticed.
     */
    public function __construct(
        public array $values,
        public ?string $summary = null,
        public array $findings = [],
    ) {}

    /**
     * Returns an answer from the JSON {@see toJson()} returns, or `null` if the text isn’t JSON, such as a plain-text
     * reply.
     *
     * The findings keep only the keys of their fields, which is all the model gave.
     */
    public static function fromJson(string $json): ?self
    {
        $answer = Json::decodeIfJson($json);

        if (! is_array($answer)) {
            return null;
        }

        $findings = array_filter(is_array($answer['findings'] ?? null) ? $answer['findings'] : [], is_array(...));

        return new self(
            values: is_array($answer['values'] ?? null) ? $answer['values'] : [],
            summary: is_string($answer['summary'] ?? null) ? $answer['summary'] : null,
            findings: array_values(array_map(static fn (array $finding): Finding => new Finding(
                key: is_string($finding['field'] ?? null) ? $finding['field'] : null,
                path: null,
                label: null,
                quote: is_string($finding['quote'] ?? null) ? $finding['quote'] : null,
                issue: is_string($finding['issue'] ?? null) ? $finding['issue'] : '',
            ), $findings)),
        );
    }

    /**
     * Returns the findings and values as the model submitted them, through {@see ProposeChanges}.
     *
     * @return array{findings: list<array{field: string|null, quote: string|null, issue: string}>, values: array<string, mixed>}
     */
    public function toArguments(): array
    {
        return [
            'findings' => array_map(static fn (Finding $finding): array => [
                'field' => $finding->key,
                'quote' => $finding->quote,
                'issue' => $finding->issue,
            ], $this->findings),
            'values' => $this->values,
        ];
    }

    /**
     * Returns the answer as JSON, in the shape the model returned it, so it can be sent back as an earlier turn of the
     * conversation.
     */
    public function toJson(): string
    {
        ['findings' => $findings, 'values' => $values] = $this->toArguments();

        return Json::encode([
            'findings' => $findings,
            'summary' => $this->summary,
            'values' => $values,
        ]);
    }
}
