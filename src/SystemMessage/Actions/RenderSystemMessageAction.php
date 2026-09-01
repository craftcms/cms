<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Actions;

use CraftCms\Cms\Email\Data\EmailSettings;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessageRenderContext;
use CraftCms\Cms\SystemMessage\SystemMessages;
use InvalidArgumentException;

use function CraftCms\Cms\renderSandboxedString;

readonly class RenderSystemMessageAction
{
    public function __construct(
        private SystemMessages $systemMessages,
        private Sites $sites,
        private SystemMessageRenderContext $renderContext,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     */
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

            $resolved = EmailSettings::fromProjectConfig()->resolveForSite($siteId);

            $variables += [
                'emailKey' => $key,
                'fromEmail' => $resolved->fromEmail,
                'replyToEmail' => $resolved->replyToEmail,
                'fromName' => $resolved->fromName,
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
                htmlBody: Markdown::parse($escapedHtmlBody, 'gfm-comment'),
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
}
