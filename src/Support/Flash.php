<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Illuminate\Support\Str;

use function CraftCms\Cms\t;

/**
 * Flashes user-facing messages to the next page load.
 *
 * On control panel requests every message is appended to one list, which the
 * Inertia app and the legacy Twig layout both read via {@see self::all()}, so a
 * message renders the same way no matter which kind of page picks it up. Each
 * message carries a unique `id` the browser uses to avoid showing it twice.
 *
 * Site requests keep Laravel's plain `notice`/`success`/`error` session keys,
 * which is what front-end templates read.
 *
 * @phpstan-type Message array{
 *     id: string,
 *     type: 'notice'|'success'|'error',
 *     message: string,
 *     settings: array<string, mixed>,
 *     target: string|null,
 * }
 */
class Flash
{
    public const string SESSION_KEY = 'cp-messages';

    /**
     * A request header naming the inline outlet (e.g. the area beside a form's
     * submit button) that messages flashed during the request belong to.
     * Messages without a target, or whose target isn't on the page, appear in
     * the default message display.
     */
    public const string TARGET_HEADER = 'X-Craft-Message-Target';

    private const string LEGACY_IDS_ATTRIBUTE = 'craft.flash.legacy-ids';

    /** @param array<string, mixed> $settings */
    public static function notice(?string $default = null, array $settings = [], ?string $target = null): ?string
    {
        return self::flash('notice', request('notice', $default), $settings, $target);
    }

    /** @param array<string, mixed> $settings */
    public static function success(?string $default = null, array $settings = [], ?string $target = null): ?string
    {
        return self::flash('success', request()->getSigned('successMessage', $default), $settings, $target);
    }

    /** @param array<string, mixed> $settings */
    public static function error(?string $default = null, array $settings = [], ?string $target = null): ?string
    {
        return self::flash('error', request()->getSigned('failMessage', $default), $settings, $target);
    }

    /**
     * Builds a message without flashing it, e.g. to return in the body of
     * a JSON response.
     *
     * @param  'notice'|'success'|'error'  $type
     * @param  array<string, mixed>  $settings
     * @return Message
     */
    public static function make(string $type, string $message, array $settings = [], ?string $target = null): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => $type,
            'message' => $message,
            'settings' => $settings + self::defaultSettings($type),
            'target' => $target ?? self::requestedTarget(),
        ];
    }

    /**
     * Adds a message to the list shown on the next control panel page.
     *
     * @param  Message  $flashed
     */
    public static function push(array $flashed): void
    {
        session()->flash(self::SESSION_KEY, [
            ...session()->get(self::SESSION_KEY, []),
            $flashed,
        ]);
    }

    /**
     * Every message waiting to be shown on this control panel request.
     *
     * Messages flashed straight to the plain `notice`/`success`/`error` keys —
     * `back()->with('success', …)` in a plugin, say — are included too, so
     * they aren't lost on pages that only read the list.
     *
     * @return list<Message>
     */
    public static function all(): array
    {
        /** @var list<Message> $messages */
        $messages = array_values(session()->get(self::SESSION_KEY, []));

        foreach (['notice', 'success', 'error'] as $type) {
            $message = session()->get($type);

            if (! is_string($message) || $message === '') {
                continue;
            }

            $alreadyListed = array_any(
                $messages,
                fn (array $existing) => $existing['type'] === $type && $existing['message'] === $message,
            );

            if (! $alreadyListed) {
                $messages[] = self::legacyMessage($type, $message);
            }
        }

        return $messages;
    }

    public static function getNotice(): ?string
    {
        return self::latest('notice');
    }

    public static function getSuccess(): ?string
    {
        return self::latest('success');
    }

    public static function getError(): ?string
    {
        return self::latest('error');
    }

    /** @param array<string, mixed> $settings */
    private static function flash(string $type, mixed $message, array $settings, ?string $target): ?string
    {
        if (! is_string($message)) {
            return null;
        }

        if (! request()->isCpRequest()) {
            session()->flash($type, $message);

            return $message;
        }

        /** @var 'notice'|'success'|'error' $type */
        self::push(self::make($type, $message, $settings, $target));

        return $message;
    }

    private static function latest(string $type): ?string
    {
        if (! request()->isCpRequest()) {
            $message = session()->get($type);

            return is_string($message) ? $message : null;
        }

        $matching = array_filter(self::all(), fn (array $flashed) => $flashed['type'] === $type);

        return array_last($matching)['message'] ?? null;
    }

    /**
     * Wraps a message flashed to a plain session key as a message. Its id
     * is remembered for the rest of the request, so the Inertia props and the
     * getters agree on it.
     *
     * @param  'notice'|'success'|'error'  $type
     * @return Message
     */
    private static function legacyMessage(string $type, string $message): array
    {
        $key = "$type:$message";
        $ids = request()->attributes->get(self::LEGACY_IDS_ATTRIBUTE, []);
        $ids[$key] ??= (string) Str::uuid();
        request()->attributes->set(self::LEGACY_IDS_ATTRIBUTE, $ids);

        return [
            'id' => $ids[$key],
            'type' => $type,
            'message' => $message,
            'settings' => self::defaultSettings($type),
            'target' => null,
        ];
    }

    /** @return array{icon: string, iconLabel: string} */
    private static function defaultSettings(string $type): array
    {
        return match ($type) {
            'success' => ['icon' => 'check', 'iconLabel' => t('Success')],
            'error' => ['icon' => 'alert', 'iconLabel' => t('Error')],
            default => ['icon' => 'info', 'iconLabel' => t('Notice')],
        };
    }

    private static function requestedTarget(): ?string
    {
        $target = request()->header(self::TARGET_HEADER);

        return is_string($target) && preg_match('/^[\w\-.:]{1,64}$/', $target) ? $target : null;
    }
}
