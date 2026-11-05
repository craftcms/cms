<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\User\Users;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * The authenticated user’s language preferences.
 *
 * @since 6.0.0
 */
readonly class Preferences
{
    public function __construct(
        private McpActor $actor,
        private Users $users,
    ) {}

    /** @return array{language: string|null, locale: string|null} */
    #[McpTool(
        name: 'preferences.get',
        description: 'Returns the authenticated user’s preferred control panel language and formatting locale. A null locale means dates and numbers are formatted for the language.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('accessCp')]
    public function get(): array
    {
        $preferences = $this->users->getUserPreferences($this->userId());

        return [
            'language' => $preferences['language'] ?? null,
            'locale' => $preferences['locale'] ?? null,
        ];
    }

    /**
     * @param  string|null  $language  A control panel language ID, such as `en-US` or `de`.
     * @param  string|null  $locale  A locale ID for formatting dates and numbers, such as `en-GB`. Pass null to use the language’s formatting.
     * @return array{language: string|null, locale: string|null}
     */
    #[McpTool(
        name: 'preferences.update',
        description: 'Changes the authenticated user’s preferred control panel language or formatting locale, as their Preferences page does. Omitted values are left as they are.',
        annotations: new ToolAnnotations(destructiveHint: false, idempotentHint: true),
    )]
    #[RequiresPermission('accessCp')]
    public function update(RequestContext $context, ?string $language = null, ?string $locale = null): array
    {
        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);
        $preferences = [];

        if (array_key_exists('language', $request->arguments)) {
            if ($language === null || I18N::getAppLocaleIds()->doesntContain($language)) {
                throw new ToolCallException("Unknown language [$language]. Available languages: ".I18N::getAppLocaleIds()->join(', ').'.');
            }

            $preferences['language'] = $language;
        }

        if (array_key_exists('locale', $request->arguments)) {
            if ($locale !== null && I18N::getAllLocaleIds()->doesntContain($locale)) {
                throw new ToolCallException("Unknown locale [$locale].");
            }

            $preferences['locale'] = $locale;
        }

        if ($preferences !== []) {
            $this->users->saveUserPreferences($this->actor->user(), $preferences);
        }

        return $this->get();
    }

    private function userId(): int
    {
        return $this->actor->user()->getCraftUserId()
            ?? throw new ToolCallException('Preferences are only available to saved users.');
    }
}
