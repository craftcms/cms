<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage;

use Closure;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function CraftCms\Cms\t;

/**
 * Internal message catalog backing {@see SystemMessages} registration.
 *
 * @internal
 */
#[Singleton]
class SystemMessageCatalog
{
    /** @var array<string, Closure> */
    private array $messages = [];

    public function __construct(
        private readonly Container $container,
    ) {
        foreach (['account_activation', 'comment_mention', 'verify_new_email', 'forgot_password', 'test_email'] as $key) {
            $this->register($key, fn () => new SystemMessage([
                'key' => $key,
                'heading' => t("{$key}_heading"),
                'subject' => t("{$key}_subject"),
                'body' => t("{$key}_body"),
            ]));
        }
    }

    /** @param Closure $resolve A container-invoked factory that returns a system message. */
    public function register(string $key, Closure $resolve): void
    {
        $this->validateKey($key);

        $this->messages[$key] = $resolve;
    }

    public function remove(string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        foreach ($keys as $key) {
            unset($this->messages[$key]);
        }
    }

    /** @return Collection<string, SystemMessage> */
    public function messages(): Collection
    {
        $language = app()->getLocale();

        if (I18N::getSiteLocaleIds()->doesntContain($language)) {
            app()->setLocale(Sites::getPrimarySite()->getLanguage());
        }

        try {
            return collect($this->messages)
                ->map(function (Closure $resolve, string $key): SystemMessage {
                    $message = $this->container->call($resolve);

                    if (! $message instanceof SystemMessage) {
                        throw new InvalidArgumentException(sprintf('System message factory [%s] must return [%s].', $key, SystemMessage::class));
                    }

                    if ($message->key !== $key) {
                        throw new InvalidArgumentException(sprintf('System message factory [%s] returned message key [%s].', $key, $message->key));
                    }

                    return $message;
                })
                ->keyBy('key')
                ->sortKeys();
        } finally {
            app()->setLocale($language);
        }
    }

    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('System message keys cannot be empty.');
        }
    }
}
