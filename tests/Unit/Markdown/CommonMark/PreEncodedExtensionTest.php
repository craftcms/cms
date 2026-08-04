<?php

declare(strict_types=1);

use CraftCms\Cms\Markdown\CommonMark\Extensions\PreEncodedExtension;
use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedCodeRenderer;
use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedFencedCodeRenderer;
use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedIndentedCodeRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;

describe('PreEncodedExtension', function () {
    it('registers the custom pre-encoded renderers', function () {
        $environment = new Environment;
        $extension = new PreEncodedExtension;

        $extension->register($environment);

        $codeRenderers = iterator_to_array($environment->getRenderersForClass(Code::class));
        $fencedCodeRenderers = iterator_to_array($environment->getRenderersForClass(FencedCode::class));
        $indentedCodeRenderers = iterator_to_array($environment->getRenderersForClass(IndentedCode::class));

        expect($codeRenderers)->toHaveCount(1)
            ->and($codeRenderers[0])->toBeInstanceOf(PreEncodedCodeRenderer::class)
            ->and($fencedCodeRenderers)->toHaveCount(1)
            ->and($fencedCodeRenderers[0])->toBeInstanceOf(PreEncodedFencedCodeRenderer::class)
            ->and($indentedCodeRenderers)->toHaveCount(1)
            ->and($indentedCodeRenderers[0])->toBeInstanceOf(PreEncodedIndentedCodeRenderer::class);
    });
});
