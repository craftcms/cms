<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\mcp\attributes\RequiresPermission;
use craft\mcp\McpActor;
use craft\services\Users;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * The authenticated user’s language preferences.
 *
 * @since 5.12.0
 */
class Preferences
{
    public function __construct(
        private McpActor $actor,
        private Users $users,
    ) {
    }

    /** @return array{language: string|null, locale: string|null} */
    #[McpTool(
        name: 'preferences.get',
        description: 'Returns the authenticated user’s preferred control panel language and formatting locale. A null locale means dates and numbers are formatted for the language.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresPermission('accessCp')]
    public function get(): array
    {
        $preferences = $this->users->getUserPreferences($this->actor->user()->id);

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
        $i18n = Craft::$app->getI18n();
        $preferences = [];

        if (array_key_exists('language', $request->arguments)) {
            $languages = $i18n->getAppLocaleIds();

            if ($language === null || !in_array($language, $languages, true)) {
                throw new ToolCallException("Unknown language [$language]. Available languages: " . implode(', ', $languages) . '.');
            }

            $preferences['language'] = $language;
        }

        if (array_key_exists('locale', $request->arguments)) {
            if ($locale !== null && !in_array($locale, $i18n->getAllLocaleIds(), true)) {
                throw new ToolCallException("Unknown locale [$locale].");
            }

            $preferences['locale'] = $locale;
        }

        if ($preferences !== []) {
            $this->users->saveUserPreferences($this->actor->user(), $preferences);
        }

        return $this->get();
    }
}
