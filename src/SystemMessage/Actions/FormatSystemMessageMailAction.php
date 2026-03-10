<?php

declare(strict_types=1);

namespace CraftCms\Cms\SystemMessage\Actions;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\SystemMessage\Data\FormattedSystemMessageMail;
use CraftCms\Cms\SystemMessage\Data\RenderedSystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessageRenderContext;
use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\View\TemplateMode;

final readonly class FormatSystemMessageMailAction
{
    public function __construct(
        private GeneralConfig $generalConfig,
        private SystemMessageRenderContext $renderContext,
        private TemplateRenderer $templateRenderer,
    ) {}

    public function handle(RenderedSystemMessage $message): FormattedSystemMessageMail
    {
        $viewData = array_merge($message->variables, [
            'subject' => $message->subject,
            'textBody' => $message->textBody,
            'htmlBody' => $message->htmlBody,
            'emailKey' => $message->key,
            'language' => $message->language,
        ]);

        if ($this->generalConfig->systemMessageTemplate === null) {
            return new FormattedSystemMessageMail(
                usesCustomTemplate: false,
                htmlBody: $message->htmlBody,
                viewData: $viewData,
            );
        }

        $htmlBody = $this->renderContext->run(
            siteId: $message->siteId,
            language: $message->language,
            callback: fn () => $this->templateRenderer->renderTemplate(
                template: $this->generalConfig->systemMessageTemplate,
                variables: $viewData,
                templateMode: TemplateMode::Site,
            ),
        );

        return new FormattedSystemMessageMail(
            usesCustomTemplate: true,
            htmlBody: $htmlBody,
            viewData: $viewData,
        );
    }
}
