<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Support\Url;

function entryElementForPostEditUrl(?Section $section): Entry
{
    $entry = new class extends Entry
    {
        public ?Section $mockSection = null;

        public function getSection(): ?Section
        {
            return $this->mockSection;
        }
    };

    $entry->mockSection = $section;

    return $entry;
}

describe('getPostEditUrl', function () {
    test('returns the section page when one is defined', function () {
        $section = new class extends Section
        {
            public ?string $mockPage = null;

            public function getPage(): ?string
            {
                return $this->mockPage;
            }
        };
        $section->mockPage = 'Marketing Pages';

        $entry = entryElementForPostEditUrl($section);

        expect($entry->getPostEditUrl())->toBe(Url::cpUrl('content/marketing-pages'));
    });

    test('falls back to the entries page when the section has no page', function () {
        $entry = entryElementForPostEditUrl(null);

        expect($entry->getPostEditUrl())->toBe(Url::cpUrl('content/entries'));
    });
});
