<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Mixins;

use Closure;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Json;
use Illuminate\Testing\Assert;
use Illuminate\Testing\TestResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assertions on the control panel messages a test response delivers, for
 * Craft's own tests and for plugin and module tests.
 *
 *     post(cp_url('settings/sites'), $data)->assertCpMessage('success', 'Site saved.');
 *     postJson(cp_url('actions/entries/save-entry'), $data)->assertCpMessage(fn (array $message) => $message['target'] === 'save');
 *     get(cp_url('dashboard'))->assertNoCpMessages();
 *
 * @phpstan-import-type Message from Flash
 *
 * @since 6.0.0
 */
class TestResponseMixin
{
    /**
     * The messages the response delivers to the browser: those in an Inertia
     * page's `messages` prop, those flashed for the page a redirect or an
     * Inertia hard visit leads to, those a Twig page queues in
     * `window.CraftMessageQueue`, and those in the `messages` list of a JSON
     * body, as {@see RespondsWithFlash::asSuccess()} returns.
     *
     * Messages a response leaves in the session without redirecting aren't
     * included, since the browser shows them on some later page.
     *
     * Every assertion reads messages through this method, so a new way of
     * delivering them only needs to be added here.
     */
    public function cpMessages(): Closure
    {
        return function (): array {
            /**
             * @var TestResponse<Response> $response
             *
             * @phpstan-ignore-next-line
             */
            $response = $this;
            $messages = match (true) {
                $response->headers->has('X-Inertia') => $response->json('props.messages'),
                $response->isRedirect(), $response->headers->has('X-Inertia-Location') => Flash::all(),
                ($response->baseResponse->original ?? null) instanceof View => $response->baseResponse->original->getData()['page']['props']['messages'] ?? [],
                $response->baseResponse instanceof JsonResponse => $response->json('messages'),
                // `_layouts/components/messages.twig` writes the queue on one line, and its JSON escapes `</script>`.
                is_string($html = $response->baseResponse->getContent())
                    && preg_match('/\(window\.CraftMessageQueue = window\.CraftMessageQueue \|\| \[\]\)\.push\(\.\.\.(\[.*\])\);/', $html, $queue) === 1 => Json::decode($queue[1]),
                default => [],
            };

            return is_array($messages) ? array_values($messages) : [];
        };
    }

    /**
     * Asserts the response delivers a message of the given type, and
     * optionally text. Pass a callback instead to match messages, which
     * receives each one as an array with `id`, `type`, `message`, `settings`,
     * and `target` keys.
     */
    public function assertCpMessage(): Closure
    {
        return function (string|Closure $type, ?string $message = null): TestResponse {
            /**
             * @var TestResponse<Response> $response
             *
             * @phpstan-ignore-next-line
             */
            $response = $this;
            $messages = $response->cpMessages();

            $matches = $type instanceof Closure
                ? array_filter($messages, $type)
                : array_filter($messages, fn (array $delivered): bool => $delivered['type'] === $type
                    && ($message === null || $delivered['message'] === $message));

            $expected = match (true) {
                $type instanceof Closure => 'a message matching the callback',
                $message === null => "a [$type] message",
                default => "the [$type] message “{$message}”",
            };

            $delivered = implode("\n", array_map(fn (array $m): string => "- [{$m['type']}] {$m['message']}", $messages)) ?: '(none)';

            Assert::assertNotEmpty($matches, "The response didn’t deliver $expected. Delivered messages:\n$delivered");

            return $response;
        };
    }

    /**
     * Asserts the response delivers no messages, or none of the given type.
     */
    public function assertNoCpMessages(): Closure
    {
        return function (?string $type = null): TestResponse {
            /**
             * @var TestResponse<Response> $response
             *
             * @phpstan-ignore-next-line
             */
            $response = $this;
            $messages = $response->cpMessages();

            $matches = $type === null
                ? $messages
                : array_filter($messages, fn (array $delivered): bool => $delivered['type'] === $type);

            $unexpected = $type === null ? 'messages' : "[$type] messages";

            $delivered = implode("\n", array_map(fn (array $m): string => "- [{$m['type']}] {$m['message']}", $matches));

            Assert::assertEmpty($matches, "The response delivered unexpected $unexpected:\n$delivered");

            return $response;
        };
    }
}
