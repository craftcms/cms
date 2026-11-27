<?php

declare(strict_types=1);

use CraftCms\Cms\Import\Data\MappingTemplate;

it('reads the data paths a template uses from shorthand tags and object references', function () {
    $template = new MappingTemplate("{a.b|upper} {{ object.c['D e'] }} {% if x %}{f}{% endif %} {clone(g)} {h: 1}");

    expect($template->paths)->toEqualCanonicalizing(['a.b', 'c.D e', 'f']);
});

it('doesn’t read data paths inside a verbatim block', function () {
    expect(new MappingTemplate('{% verbatim %}{a}{% endverbatim %}{b}')->paths)->toBe(['b']);
});

it('doesn’t read the incoming data’s reserved keys as data paths', function () {
    $template = new MappingTemplate('{title} {matchCriteria.title} {{ object.blocks.clearableItems }} {keepMissingNestedElements}');

    expect($template->paths)->toBe(['title']);
});
