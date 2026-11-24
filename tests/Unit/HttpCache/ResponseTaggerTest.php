<?php

declare(strict_types=1);

use CraftCms\Cms\HttpCache\ResponseTagger;

it('ignores empty and duplicate tags', function () {
    $tagger = (new ResponseTagger)->addTags(['a', '', 'b'])->addTags('a');

    expect($tagger->getTags())->toBe(['a', 'b']);
});

it('captures the tags added inside nested blocks', function () {
    $tagger = new ResponseTagger;

    $tagger->addTags(['page']);
    $tagger->startCapture();
    $tagger->addTags(['outer', 'page']);
    $tagger->startCapture();
    $tagger->addTags(['inner']);

    expect($tagger->stopCapture())->toBe(['inner'])
        ->and($tagger->stopCapture())->toBe(['outer', 'page', 'inner']);
});
