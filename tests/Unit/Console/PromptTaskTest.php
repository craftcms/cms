<?php

declare(strict_types=1);

use CraftCms\Cms\Console\FallbackPromptLogger;
use Symfony\Component\Console\Output\BufferedOutput;

it('writes logger messages to fallback output', function () {
    $output = new BufferedOutput;
    $logger = new FallbackPromptLogger($output);

    $logger->line('line');
    $logger->success('success');
    $logger->warning('warning');
    $logger->error('error');
    $logger->label('label');
    $logger->subLabel('sub-label');

    expect($output->fetch())->toContain('line')
        ->toContain('DONE success')
        ->toContain('WARN warning')
        ->toContain('FAIL error')
        ->toContain('label')
        ->toContain('sub-label');
});
