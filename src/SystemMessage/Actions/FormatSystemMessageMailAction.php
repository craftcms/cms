<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Actions;

use CraftCms\Cms\Email\Data\MailSettings;
use CraftCms\Cms\SystemMessage\Data\FormattedSystemMessageMail;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessageRenderContext;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;

readonly class FormatSystemMessageMailAction
{
    public function __construct(
        private SystemMessageRenderContext $renderContext,
        private TemplateManager $templateManager,
    ) {}

    public function handle(RenderedSystemMessage $message, MailSettings $settings): FormattedSystemMessageMail
    {
        $viewData = array_merge($message->variables, [
            'subject' => $message->subject,
            'textBody' => $message->textBody,
            'htmlBody' => $message->htmlBody,
            'emailKey' => $message->key,
            'language' => $message->language,
        ]);

        if ($settings->template === null) {
            return new FormattedSystemMessageMail(
                usesCustomTemplate: false,
                htmlBody: $message->htmlBody,
                viewData: $viewData,
            );
        }

        $htmlBody = $this->renderContext->run(
            siteId: $message->siteId,
            language: $message->language,
            callback: fn () => $this->templateManager->renderTemplate(
                template: $settings->template,
                variables: $viewData,
                templateMode: TemplateMode::Site,
                renderer: TemplateEngine::Twig,
            ),
        );

        return new FormattedSystemMessageMail(
            usesCustomTemplate: true,
            htmlBody: $htmlBody,
            viewData: $viewData,
        );
    }
}
