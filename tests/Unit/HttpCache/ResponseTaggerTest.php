<?php

declare(strict_types=1);

use CraftCms\Cms\HttpCache\ResponseTagger;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use Symfony\Component\HttpFoundation\Response;

it('writes tags with the formatter and keeps them for drivers', function () {
    $tagger = new ResponseTagger(new CommaSeparatedTagHeaderFormatter('Surrogate-Key', ' '));
    $response = new Response(headers: ['Surrogate-Key' => 'existing']);

    $tagger->addTags(['a', '', 'b'])->addTags('a');
    $tagger->tagResponse($response);

    expect($response->headers->all('surrogate-key'))->toBe(['existing', 'a b'])
        ->and($tagger->getTags())->toBe(['a', 'b']);
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
