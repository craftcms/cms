<?php

declare(strict_types=1);

use CraftCms\Cms\HttpCache\DefaultTagEncoder;
use CraftCms\Cms\HttpCache\ResponseTagger;
use CraftCms\Cms\HttpCache\TagEncoder;
use FOS\HttpCache\TagHeaderFormatter\CommaSeparatedTagHeaderFormatter;
use Symfony\Component\HttpFoundation\Response;

it('writes encoded tags with the formatter and keeps them for drivers', function () {
    $encoder = new class implements TagEncoder
    {
        public function encode(array $tags): array
        {
            return array_map(fn (string $tag) => "env-$tag", $tags);
        }
    };
    $tagger = new ResponseTagger($encoder, new CommaSeparatedTagHeaderFormatter('Surrogate-Key', ' '));
    $response = new Response(headers: ['Surrogate-Key' => 'existing']);

    $tagger->addTags(['a', '', 'b'])->addTags('a');
    $tagger->tagResponse($response);

    expect($response->headers->all('surrogate-key'))->toBe(['existing', 'env-a env-b'])
        ->and($tagger->getTags())->toBe(['a', 'b']);
});

it('captures the tags added inside nested blocks', function () {
    $tagger = new ResponseTagger(new DefaultTagEncoder);

    $tagger->addTags(['page']);
    $tagger->startCapture();
    $tagger->addTags(['outer', 'page']);
    $tagger->startCapture();
    $tagger->addTags(['inner']);

    expect($tagger->stopCapture())->toBe(['inner'])
        ->and($tagger->stopCapture())->toBe(['outer', 'page', 'inner']);
});
