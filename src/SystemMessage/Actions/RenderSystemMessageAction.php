<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Actions;

use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessageRenderContext;
use CraftCms\Cms\SystemMessage\SystemMessages;
use InvalidArgumentException;
use yii\helpers\Markdown;

use function CraftCms\Cms\renderSandboxedString;

final readonly class RenderSystemMessageAction
{
    public function __construct(
        private SystemMessages $systemMessages,
        private Sites $sites,
        private SystemMessageRenderContext $renderContext,
    ) {}

    public function handle(
        string $key,
        array $variables = [],
        ?string $language = null,
        ?int $siteId = null,
    ): RenderedSystemMessage {
        $language = $this->resolveLanguage($language, $siteId);

        return $this->renderContext->run($siteId, $language, function () use ($key, $variables, $language, $siteId) {
            $systemMessage = $this->systemMessages->getMessage($key, $language);

            if ($systemMessage === null) {
                throw new InvalidArgumentException("Invalid system message key: $key");
            }

            $variables += [
                'emailKey' => $key,
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
                key: $key,
                language: $language,
                subject: $subject,
                textBody: $textBody,
                htmlBody: Markdown::process($escapedHtmlBody, 'gfm'),
                siteId: $siteId,
                variables: $variables,
            );
        });
    }

    private function resolveLanguage(?string $language, ?int $siteId): string
    {
        if ($language !== null) {
            return $language;
        }

        if ($siteId !== null && ($site = $this->sites->getSiteById($siteId)) !== null) {
            return $site->getLanguage();
        }

        return request()->isSiteRequest()
            ? app()->getLocale()
            : $this->sites->getPrimarySite()->getLanguage();
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
