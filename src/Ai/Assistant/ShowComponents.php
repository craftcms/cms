<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Component\Contracts\CpEditable;
use CraftCms\Cms\Component\Contracts\Iconic;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Data\UserGroup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * The AI assistant’s tool that shows settings to the user, such as sections and fields, as chips that link to where
 * they’re edited. Only admins have it, as only they can see settings with Craft’s MCP tools.
 *
 * Like {@see ShowElements}, the model, and the stored conversation, only get what identifies each one. Their chips are
 * added on the way to the chat.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ShowComponents implements Tool
{
    public const string NAME = 'show_components';

    /**
     * The most settings a call shows.
     */
    private const int MAX = 20;

    /**
     * The kinds of settings it shows, by the type the model names.
     *
     * @var array<string, class-string<Chippable>>
     */
    private const array TYPES = [
        'sections' => Section::class,
        'entryTypes' => EntryType::class,
        'fields' => Field::class,
        'userGroups' => UserGroup::class,
    ];

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Shows settings to the user, such as sections, entry types, fields, and user groups, as chips that link to where they’re edited, which the user opens themselves. Whenever your reply names particular settings, such as the fields of an entry type, show them with this as well, even when the user only asked about them.';
    }

    /**
     * Returns the settings to show as the model sees them, and as the conversation stores them. The chat gets their
     * chips from {@see forDisplay()}.
     */
    public function handle(Request $request): string
    {
        $items = $request->all()['components'] ?? [];
        $components = [];
        $missing = 0;

        foreach (array_slice(is_array($items) ? $items : [], 0, self::MAX) as $item) {
            $found = is_array($item) ? $this->find($item) : null;

            if ($found === null) {
                $missing++;

                continue;
            }

            [$type, $component] = $found;
            $components[] = ['type' => $type, 'id' => $component->getId(), 'label' => $component->getUiLabel()];
        }

        $message = sprintf('Showed %d setting(s).', count($components));

        if ($missing > 0) {
            $message .= sprintf(' %d couldn’t be found.', $missing);
        }

        return Json::encode(['message' => $message, 'components' => $components]);
    }

    /**
     * Returns a result of the tool for the chat to show: each setting’s chip, as of now. Settings that no longer exist
     * are left out.
     */
    public function forDisplay(string $result): string
    {
        $decoded = Json::decodeIfJson($result);

        if (! is_array($decoded) || ! is_array($decoded['components'] ?? null)) {
            return $result;
        }

        $shown = [];

        foreach ($decoded['components'] as $item) {
            $found = is_array($item) ? $this->find($item) : null;

            if ($found === null) {
                continue;
            }

            [$type, $component] = $found;
            $url = $component instanceof CpEditable ? $component->getCpEditUrl() : null;
            $icon = $component instanceof Iconic ? $component->getIcon() : null;

            $shown[] = [
                'type' => $type,
                'id' => $component->getId(),
                'label' => $component->getUiLabel(),
                // With its family, as some, such as Markdown fields’, are brand icons.
                'icon' => $icon !== null && $icon !== '' ? Icons::resolveIconData($icon) : null,
                'cpEditUrl' => $url !== null ? Url::cpUrl($url) : null,
            ];
        }

        return Json::encode([...$decoded, 'components' => $shown]);
    }

    /**
     * @param  array<mixed>  $item
     * @return array{string, Chippable}|null The type, and the setting.
     */
    private function find(array $item): ?array
    {
        $type = is_string($item['type'] ?? null) ? $item['type'] : null;
        $class = $type !== null ? self::TYPES[$type] ?? null : null;
        $id = is_int($item['id'] ?? null) ? $item['id'] : null;
        $component = $class !== null && $id !== null ? $class::get($id) : null;

        return $type !== null && $component !== null ? [$type, $component] : null;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'components' => $schema->array()
                ->items($schema->object([
                    'type' => $schema->string()->enum(array_keys(self::TYPES))->description('The kind of setting.')->required(),
                    'id' => $schema->integer()->description('Its ID.')->required(),
                ]))
                ->description('The settings to show, in order.')
                ->required(),
        ];
    }
}
