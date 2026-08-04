<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\CommonMark\Extensions;

use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedCodeRenderer;
use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedFencedCodeRenderer;
use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedIndentedCodeRenderer;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\ExtensionInterface;

class PreEncodedExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addRenderer(Code::class, new PreEncodedCodeRenderer, 10)
            ->addRenderer(FencedCode::class, new PreEncodedFencedCodeRenderer, 10)
            ->addRenderer(IndentedCode::class, new PreEncodedIndentedCodeRenderer, 10);
    }
}
