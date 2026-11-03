<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Support\Json;
use Laravel\Ai\Models\ConversationMessage;

/**
 * The block at the start of a user’s message that lists what they sent it with, which the control panel adds:
 *
 * ```
 * <context>[{"type":"craft:element","ref":"…","label":"Blog post","icon":"file-lines","data":{…}}]</context>
 * ```
 *
 * It’s stored with the message, so the model reads it as part of the message, and the chat shows it again. Each item’s
 * `data` is the reference the control panel kept of it, which later messages resolve it again from.
 *
 * @internal
 *
 * @since 6.0.0
 */
class MessageContext
{
    private const string PATTERN = '/^<context>(.*?)<\/context>\n\n/s';

    /**
     * Returns a message’s text without its context block.
     */
    public static function strip(string $content): string
    {
        return preg_replace(self::PATTERN, '', $content, 1) ?? $content;
    }

    /**
     * Returns the items a conversation’s messages were sent with, the latest first, as context types take them, and
     * marked as `earlier`. Each is included once, and only if it kept a reference.
     *
     * @return list<array<string, mixed>>
     */
    public static function earlierItems(string $conversationId): array
    {
        $contents = ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->where('role', 'user')
            ->latest()
            ->orderByDesc('id')
            ->pluck('content');

        $items = [];

        foreach ($contents as $content) {
            foreach (self::entries((string) $content) as $entry) {
                $data = $entry['data'] ?? null;
                $type = $entry['type'] ?? null;
                $ref = $entry['ref'] ?? null;

                if (! is_array($data) || $data === [] || ! is_string($type) || ! is_string($ref)) {
                    continue;
                }

                $items["$type:$ref"] ??= [...$data, 'type' => $type, 'ref' => $ref, 'earlier' => true];
            }
        }

        return array_values($items);
    }

    /**
     * @return list<mixed>
     */
    private static function entries(string $content): array
    {
        if (! preg_match(self::PATTERN, $content, $match)) {
            return [];
        }

        $entries = Json::decodeIfJson($match[1]);

        return is_array($entries) && array_is_list($entries) ? $entries : [];
    }
}
