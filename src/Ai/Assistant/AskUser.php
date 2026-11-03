<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Responses\Data\Step;
use Laravel\Ai\Tools\Request;

/**
 * The AI assistant’s tool that asks the user a question with answers to choose from, which the chat shows as radios
 * above its message box. The answer the user chooses, or types, is their next message.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class AskUser implements Tool
{
    public const string NAME = 'ask_user';

    /**
     * The most answers a question offers.
     */
    private const int MAX_OPTIONS = 6;

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Asks the user a question, with the likeliest answers to choose from, such as which of several matching elements they mean, or which of a few suggested titles to use. They can always type another answer, so don’t offer one for that. Ask every question with this, and offer every set of suggestions or options to pick from with it, rather than in your reply. Your reply ends once you’ve asked: their answer is their next message.';
    }

    /**
     * Returns whether the latest of a turn’s steps asked the user a question, which ends the turn.
     *
     * @param  list<Step>  $steps
     */
    public static function asked(array $steps): bool
    {
        $latest = end($steps);

        foreach ($latest ? $latest->toolResults : [] as $result) {
            if ($result->name === self::NAME && $result->successful() && is_string($result->result) && str_contains($result->result, '"question"')) {
                return true;
            }
        }

        return false;
    }

    public function handle(Request $request): string
    {
        $arguments = $request->all();
        $question = is_string($arguments['question'] ?? null) ? trim($arguments['question']) : '';
        $options = array_values(array_unique(array_filter(
            array_map(static fn (mixed $option): string => is_string($option) ? trim($option) : '', is_array($arguments['options'] ?? null) ? $arguments['options'] : []),
            static fn (string $option): bool => $option !== '',
        )));

        if ($question === '' || count($options) < 2) {
            return Json::encode(['message' => 'Ask a question with at least two answers.']);
        }

        return Json::encode([
            'message' => 'Asked the user. Their answer is their next message.',
            'question' => $question,
            'options' => array_slice($options, 0, self::MAX_OPTIONS),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'question' => $schema->string()->description('The question, in one short sentence.')->required(),
            'options' => $schema->array()
                ->items($schema->string())
                ->description(sprintf('2 to %d short answers, each as the user would say it.', self::MAX_OPTIONS))
                ->required(),
        ];
    }
}
