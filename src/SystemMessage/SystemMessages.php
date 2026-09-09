<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage;

use Closure;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Value\Value;

/**
 * Manages system messages and registers additional lazily resolved messages.
 *
 * ```php
 * public function boot(SystemMessages $systemMessages): void
 * {
 *     $systemMessages->register('order_shipped', fn () => new SystemMessage([
 *         'key' => 'order_shipped',
 *         'heading' => 'Order shipped',
 *         'subject' => 'Your order has shipped',
 *         'body' => 'Your order is on its way.',
 *     ]));
 * }
 * ```
 */
#[Scoped]
class SystemMessages
{
    /** @var Collection<string, SystemMessage>|null */
    private ?Collection $defaultMessages = null;

    public function __construct(
        private readonly SystemMessageCatalog $messageCatalog,
    ) {}

    /** @param Closure $resolve A container-invoked factory that returns a system message. */
    public function register(string $key, Closure $resolve): void
    {
        $this->messageCatalog->register($key, $resolve);
    }

    public function remove(string ...$keys): void
    {
        $this->messageCatalog->remove(...$keys);
    }

    /** @return Collection<string, SystemMessage> */
    public function messages(): Collection
    {
        return $this->messageCatalog->messages();
    }

    /**
     * Returns all the default system email messages, without subject/body overrides.
     *
     * @return Collection<string, SystemMessage>
     */
    public function getAllDefaultMessages(): Collection
    {
        if (! is_null($this->defaultMessages)) {
            return $this->defaultMessages;
        }

        return $this->defaultMessages = $this->messages();
    }

    /**
     * Returns a default system email message by its key, without subject/body overrides.
     */
    public function getDefaultMessage(string $key): ?SystemMessage
    {
        return $this->getAllDefaultMessages()->firstWhere('key', $key);
    }

    /**
     * Returns all the system email messages in a given language, with subject/body overrides.
     *
     * @return Collection<string, SystemMessage>
     */
    public function getAllMessages(?string $language = null): Collection
    {
        $language ??= Sites::getPrimarySite()->getLanguage();

        // Start with the defaults
        $defaults = $this->getAllDefaultMessages();

        // Fetch any custom messages
        $overrides = SystemMessage::query()
            ->where('language', $language)
            ->get()
            ->keyBy('key');

        // Combine them to create the final messages array
        $messages = collect();

        foreach ($defaults as $key => $default) {
            $message = clone $default;

            // Has it been overridden?
            if (isset($overrides[$key])) {
                $message->subject = $overrides[$key]->subject;
                $message->body = $overrides[$key]->body;
            }

            $messages[$key] = $message;
        }

        return $messages;
    }

    /**
     * Returns a system email messages in a given language by its key, with subject/body overrides.
     */
    public function getMessage(string $key, ?string $language = null): ?SystemMessage
    {
        // Get the default message (and ensure $key is valid)
        if (($default = $this->getDefaultMessage($key)) === null) {
            return null;
        }

        $message = clone $default;

        $language ??= Sites::getPrimarySite()->getLanguage();

        if (($pos = strpos((string) $language, '-')) !== false) {
            $languageId = substr((string) $language, 0, $pos);
        } else {
            $languageId = $language;
        }

        if (Edition::get()->value >= Edition::Pro->value) {
            // Fetch the customization (if there is one)
            $override = SystemMessage::query()
                ->select(['subject', 'body'])
                ->where('key', $key)
                ->where(fn (Builder $query) => $query
                    ->whereIn('language', [$language, $languageId])
                    ->orWhereLike('language', "$languageId%")
                )
                ->orderBy(
                    new CaseGroup([
                        new CaseRule(new Value(0), new Equal('language', new Value($language))),
                        new CaseRule(new Value(1), new Equal('language', new Value($languageId))),
                    ], new Value(2))
                )
                ->first();

            if ($override) {
                $message->subject = $override->subject;
                $message->body = $override->body;
            }
        }

        $message->language = $language;

        return $message;
    }

    /**
     * Saves the subject/body overrides for a system email message.
     */
    public function saveMessage(SystemMessage $message, ?string $language = null): void
    {
        $message->language = $language ?? $message->language;

        SystemMessage::updateOrCreate([
            'key' => $message->key,
            'language' => $message->language,
        ], [
            'subject' => $message->subject,
            'body' => $message->body,
        ]);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function mailable(string $key, User $user, array $variables = []): SystemMessageMailable
    {
        $siteId = null;
        $language = $user->getPreferredLanguage();

        if (
            isset($user->affiliatedSiteId) &&
            (
                app()->runningInConsole() ||
                request()->isCpRequest()
            )
        ) {
            $siteId = $user->affiliatedSiteId;
        }

        $variables['user'] ??= $user;

        return new SystemMessageMailable(
            key: $key,
            variables: $variables,
            language: $language,
            siteId: $siteId,
        )
            ->to($user->email, $user->fullName)
            ->locale($language);
    }
}
