<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * The AI assistant’s tool that shows elements to the user as cards or chips, as the control panel draws them elsewhere,
 * with their status and actions, which the user can open. Elements the user can’t view are left out, and nested
 * elements, such as Matrix blocks, are shown as the element they’re in.
 *
 * The model, and the stored conversation, only get what identifies each element. The chips are added on the way to the
 * chat, by {@see AssistantStreamProtocol} and {@see Transcript}, so the model doesn’t pay for them, and they’re current
 * whenever they’re shown.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ShowElements implements Tool
{
    public const string NAME = 'show_elements';

    /**
     * The most elements a call shows.
     */
    private const int MAX = 20;

    /**
     * The most elements shown as cards by default. More are shown as a list of chips.
     */
    private const int MAX_CARDS = 3;

    /**
     * What each element is drawn from, by view, as the element select control sends it. A card’s own action menu is
     * drawn from `actions`, as a chip’s is.
     */
    private const array VIEW_KEYS = [
        'list' => ['id', 'label', 'siteId', 'cpEditUrl', 'status', 'color', 'thumbHtml', 'actions'],
        'cards' => ['id', 'label', 'siteId', 'cpEditUrl', 'actions', 'cardAttributes', 'cardHeaderHtml', 'cardContentHtml', 'cardFooterHtml', 'cardThumbHtml', 'thumbAlignment'],
    ];

    public function __construct(
        private ViewableElements $elements,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Shows elements to the user, which they can open and act on: as cards, which show more of each element, or as a list of chips. When you point the user to elements, such as ones you found, show them with this rather than only linking them, and don’t list them again in your reply.';
    }

    /**
     * Returns the elements to show as the model sees them, and as the conversation stores them: what identifies each
     * one, and its label. The chat gets their chips from {@see forDisplay()}.
     */
    public function handle(Request $request): string
    {
        $items = $request->all()['elements'] ?? [];
        $elements = [];
        $missing = 0;
        $nested = [];

        foreach (array_slice(is_array($items) ? $items : [], 0, self::MAX) as $item) {
            $found = is_array($item) ? $this->elements->find($item) : null;
            // A nested element, such as a Matrix block, means little on its own, so the element it’s in is shown.
            $shown = $found !== null ? $this->elements->topLevel(...$found) : null;

            if ($shown === null) {
                $missing++;

                continue;
            }

            [$type, $element] = $shown;

            if ($element !== $found[1]) {
                $nested[] = sprintf('“%s” is in “%s”', $found[1]->getUiLabel(), $element->getUiLabel());
            }

            $elements[$element->id] = ['type' => $type, 'id' => $element->id, 'siteId' => $element->siteId, 'label' => $element->getUiLabel()];
        }

        $elements = array_values($elements);

        $view = $request->all()['view'] ?? null;
        $view = is_string($view) && isset(self::VIEW_KEYS[$view]) ? $view : (count($elements) <= self::MAX_CARDS ? 'cards' : 'list');
        $message = sprintf('Showed %d element(s).', count($elements));

        if ($missing > 0) {
            $message .= sprintf(' %d couldn’t be found, or the user can’t view them.', $missing);
        }

        if ($nested !== []) {
            $message .= sprintf(' Nested elements are shown as the element they’re in: %s.', implode('; ', $nested));
        }

        return Json::encode(['message' => $message, 'view' => $view, 'elements' => $elements]);
    }

    /**
     * Returns a result of the tool for the chat to show: each element with its card or chip, as the element select
     * control sends them, as of now. Elements the user can no longer view are left out.
     */
    public function forDisplay(string $result): string
    {
        $decoded = Json::decodeIfJson($result);

        if (! is_array($decoded) || ! is_array($decoded['elements'] ?? null)) {
            return $result;
        }

        $view = ($decoded['view'] ?? null) === 'cards' ? 'cards' : 'list';
        $mode = $view === 'cards' ? ElementSelect::VIEW_MODE_CARDS : ElementSelect::VIEW_MODE_LIST;
        $shown = [];

        foreach ($decoded['elements'] as $item) {
            $found = is_array($item) ? $this->elements->find($item) : null;

            if ($found !== null) {
                $shown[] = Arr::only(ElementSelect::presentation($found[1], $mode), self::VIEW_KEYS[$view]);
            }
        }

        return Json::encode([...$decoded, 'view' => $view, 'elements' => $shown]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'elements' => $schema->array()
                ->items($schema->object([
                    'type' => $schema->string()->description('The element type, as elements.schema lists it, such as entries.')->required(),
                    'id' => $schema->integer()->description('The element’s ID.')->required(),
                    'siteId' => $schema->integer()->description('The site to show it in. Defaults to the primary site.'),
                ]))
                ->description('The elements to show, in order.')
                ->required(),
            'view' => $schema->string()
                ->enum(['cards', 'list'])
                ->description('cards for up to 3 elements, which shows more of each; list for more, as chips. Defaults to that.'),
        ];
    }
}
