<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\ContentModel\CheckRegistry;
use CraftCms\Cms\Support\Json;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class ContentModel
{
    public function __construct(
        private CheckRegistry $checkRegistry,
    ) {}

    /**
     * @param  list<string>  $checks  Checks to run. Defaults to all checks.
     * @return array{checks: list<string>, summary: array<string, int>, findings: array<string, list<mixed>>}
     */
    #[McpTool(
        name: 'content-model.audit',
        description: 'Audits the Craft CMS content model for unused fields, unused entry types, empty sections, duplicate field candidates, and image assets missing alt text.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function audit(
        #[Schema(
            description: 'Registered check IDs to run. Omit to run all checks.',
            items: ['type' => 'string'],
            uniqueItems: true,
        )]
        array $checks = [],
        #[Schema(minimum: 1, maximum: 500)]
        int $limit = 50,
    ): array {
        $registeredChecks = $this->checkRegistry->ids();
        $checks = $checks === [] ? $registeredChecks : array_values(array_unique($checks));
        $unknown = array_values(array_diff($checks, $registeredChecks));

        if ($unknown !== []) {
            throw new ToolCallException(sprintf(
                'Unsupported content-model checks: %s. Registered checks: %s.',
                implode(', ', $unknown),
                implode(', ', $registeredChecks),
            ));
        }

        $limit = max(1, min($limit, 500));
        $findings = [];
        $summary = [];

        foreach ($checks as $id) {
            $check = $this->checkRegistry->find($id);

            if ($check === null) {
                continue;
            }

            $result = $check->run($limit);
            $findings[$id] = $result['findings'];
            $summary[$id] = $result['count'];
        }

        return [
            'checks' => $checks,
            'summary' => $summary,
            'findings' => $findings,
        ];
    }

    /**
     * @param  string  $focus  Optional audit focus, such as fields, entry types, sections, or image alt text.
     * @return list<PromptMessage>
     */
    #[McpPrompt(
        name: 'audit-content-model',
        description: 'Guides a review of the Craft content model using the available MCP capabilities.',
    )]
    #[RequiresAdmin]
    public function auditPrompt(string $focus = ''): array
    {
        $focus = trim($focus);
        $checks = $this->checksFor($focus);
        $arguments = $checks === []
            ? '{}'
            : Json::encode(['checks' => $checks], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $focusInstruction = match (true) {
            $focus === '' => 'Audit the full content model.',
            $checks === [] => "Audit the full content model and give extra attention to {$focus}.",
            default => "Focus the audit on {$focus} by passing these arguments: {$arguments}.",
        };

        return [
            new PromptMessage(Role::User, new TextContent(<<<MARKDOWN
                {$focusInstruction}

                1. Run `content-model.audit` with {$arguments}.
                2. Cross-check each finding with detail capabilities such as `fields.get`, `entry-types.get`, and `sections.get`.
                3. Treat unused and duplicate items as review candidates. Check templates, modules, plugins, and external integrations before recommending removal or merging.
                4. For images without alt text, inspect their context before recommending an update with `elements.update`.
                5. Summarize what needs attention, why it was flagged, and the next non-destructive action.
                6. Do not call a destructive capability unless the user explicitly confirms each action.
                MARKDOWN)),
        ];
    }

    /** @return list<string> */
    private function checksFor(string $focus): array
    {
        $focus = mb_strtolower($focus);
        $checks = [];

        if (str_contains($focus, 'field')) {
            $checks[] = 'unused-fields';
            $checks[] = 'duplicate-field-candidates';
        }

        if (str_contains($focus, 'entry type') || str_contains($focus, 'entry-type')) {
            $checks[] = 'unused-entry-types';
            $checks[] = 'empty-entry-types';
        }

        if (str_contains($focus, 'section')) {
            $checks[] = 'empty-sections';
        }

        if (
            str_contains($focus, 'asset')
            || str_contains($focus, 'alt')
            || str_contains($focus, 'image')
        ) {
            $checks[] = 'missing-alt-text';
        }

        if ($checks === [] && in_array($focus, $this->checkRegistry->ids(), true)) {
            return [$focus];
        }

        return $checks;
    }
}
