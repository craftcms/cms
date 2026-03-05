<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Mailables;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use InvalidArgumentException;
use yii\helpers\Markdown;

use function CraftCms\Cms\renderSandboxedString;

final class SystemMessageMailable extends Mailable
{
    use Queueable;

    public function __construct(
        public string $key,
        public array $variables = [],
        public ?string $language = null,
        public ?int $siteId = null,
    ) {}

    public function renderedMessage(): RenderedSystemMessage
    {
        $language = $this->resolveLanguage($this->language, $this->siteId);

        return $this->withRenderContext($this->siteId, $language, function () use ($language) {
            $systemMessage = app(SystemMessages::class)->getMessage($this->key, $language);

            if ($systemMessage === null) {
                throw new InvalidArgumentException("Invalid system message key: $this->key");
            }

            $variables = $this->variables + [
                'emailKey' => $this->key,
                'fromEmail' => $this->fromEmail(),
                'replyToEmail' => $this->replyToEmail(),
                'fromName' => $this->fromName(),
                'language' => $language,
            ];

            $subject = renderSandboxedString($systemMessage->subject, $variables);
            $textBody = renderSandboxedString($systemMessage->body, $variables);
            $escapedHtmlBody = renderSandboxedString($systemMessage->body, $variables, escapeHtml: true);

            // Remove </> from around URLs, so they're not interpreted as HTML tags.
            $textBody = preg_replace('/<(https?:\/\/.+?)>/', '$1', $textBody) ?? $textBody;

            return new RenderedSystemMessage(
                key: $this->key,
                language: $language,
                subject: $subject,
                textBody: $textBody,
                htmlBody: Markdown::process($escapedHtmlBody, 'gfm'),
                variables: $variables,
            );
        });
    }

    public function build(): static
    {
        $message = $this->renderedMessage();

        $data = array_merge($message->variables, [
            'textBody' => $message->textBody,
            'htmlBody' => $message->htmlBody,
        ]);

        return $this
            ->subject($message->subject)
            ->markdown('mail.system-message', $data)
            ->text('mail.system-message-text', $data);
    }

    private function resolveLanguage(?string $language, ?int $siteId): string
    {
        if ($language !== null) {
            return $language;
        }

        if ($siteId !== null && ($site = Sites::getSiteById($siteId)) !== null) {
            return $site->getLanguage();
        }

        return request()->isSiteRequest()
            ? app()->getLocale()
            : Sites::getPrimarySite()->getLanguage();
    }

    private function withRenderContext(?int $siteId, string $language, callable $callback): mixed
    {
        $generalConfig = Cms::config();
        $currentSite = $messageSite = $twig = null;
        $originalLanguage = app()->getLocale();
        $generateTransformsBeforePageLoad = $generalConfig->generateTransformsBeforePageLoad;

        $originalTemplateMode = TemplateMode::get();
        TemplateMode::set(TemplateMode::Site);

        try {
            if ($siteId !== null) {
                $currentSite = Sites::getCurrentSite();

                if ($siteId !== $currentSite->id) {
                    $messageSite = Sites::getSiteById($siteId);

                    if ($messageSite) {
                        Sites::setCurrentSite($messageSite);
                        // Reset Twig so any global sets and singles get reloaded for the new site.
                        $twig = Twig::get();
                        Twig::set(Twig::create());
                    }
                }
            }

            app()->setLocale($language);

            // Temporarily disable lazy transform generation.
            $generalConfig->generateTransformsBeforePageLoad = true;

            return $callback();
        } finally {
            app()->setLocale($originalLanguage);
            $generalConfig->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;

            if ($currentSite && $messageSite) {
                Sites::setCurrentSite($currentSite);
            }

            TemplateMode::set($originalTemplateMode);

            if ($twig) {
                Twig::set($twig);
            }
        }
    }

    private function fromEmail(): ?string
    {
        return Env::configValue('mail.from.address', fallbackEnvs: ['MAIL_FROM_ADDRESS', 'FROM_EMAIL_ADDRESS']);
    }

    private function fromName(): ?string
    {
        return Env::configValue('mail.from.name', fallbackEnvs: ['MAIL_FROM_NAME', 'FROM_EMAIL_NAME']);
    }

    private function replyToEmail(): ?string
    {
        return Env::configValue('mail.reply_to.address');
    }
}
