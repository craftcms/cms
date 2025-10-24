<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Events\RegisterSystemMessages;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Value\Value;

use function CraftCms\Cms\t;

#[Singleton]
final class SystemMessages
{
    /** @var Collection<SystemMessage>|null */
    private ?Collection $defaultMessages = null;

    /**
     * Returns all the default system email messages, without subject/body overrides.
     *
     * @return Collection<SystemMessage>
     */
    public function getAllDefaultMessages(): Collection
    {
        if (! is_null($this->defaultMessages)) {
            return $this->defaultMessages;
        }

        // If the current language isn't one of the site's languages, switch to the primary site's language
        $language = app()->getLocale();
        if (! I18N::getSiteLocaleIds()->contains($language)) {
            app()->setLocale(Sites::getPrimarySite()->getLanguage());
        }

        $messages = collect([
            new SystemMessage([
                'key' => 'account_activation',
                'heading' => t('account_activation_heading'),
                'subject' => t('account_activation_subject'),
                'body' => t('account_activation_body'),
            ]),
            new SystemMessage([
                'key' => 'verify_new_email',
                'heading' => t('verify_new_email_heading'),
                'subject' => t('verify_new_email_subject'),
                'body' => t('verify_new_email_body'),
            ]),
            new SystemMessage([
                'key' => 'forgot_password',
                'heading' => t('forgot_password_heading'),
                'subject' => t('forgot_password_subject'),
                'body' => t('forgot_password_body'),
            ]),
            new SystemMessage([
                'key' => 'test_email',
                'heading' => t('test_email_heading'),
                'subject' => t('test_email_subject'),
                'body' => t('test_email_body'),
            ]),
        ]);

        if (Event::hasListeners(RegisterSystemMessages::class)) {
            Event::dispatch($event = new RegisterSystemMessages($messages));
            $messages = $event->messages;
        }

        // Sort them all by key
        $messages = $messages
            ->keyBy('key')
            ->sortBy('key');

        // Make sure they're SystemMessage objects
        foreach ($messages as $key => $message) {
            if (is_array($message)) {
                $messages[$key] = new SystemMessage($message);
            }
        }

        // Put the original language back
        app()->setLocale($language);

        return $this->defaultMessages = $messages;
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
     * @return Collection<SystemMessage>
     */
    public function getAllMessages(?string $language = null): Collection
    {
        if ($language === null) {
            $language = Sites::getPrimarySite()->getLanguage();
        }

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

        if ($language === null) {
            $language = Sites::getPrimarySite()->getLanguage();
        }

        if (($pos = strpos($language, '-')) !== false) {
            $languageId = substr($language, 0, $pos);
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
}
