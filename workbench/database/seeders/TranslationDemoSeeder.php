<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * A multi-site, multi-lingual install for trying out translation: French and German sites beside the primary one,
 * and an Exhibitions section whose entry is fully translated into French except for one field, untranslated in
 * German, and has a draft that reorders, edits, adds, and removes its Matrix sections.
 *
 * Not part of the default seed, which stays single-site:
 *
 *     php vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\TranslationDemoSeeder'
 */
class TranslationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $primary = Sites::getPrimarySite();
        $french = $this->site('french', 'Français', 'fr-FR', $primary);
        $german = $this->site('german', 'Deutsch', 'de-DE', $primary);

        $summary = $this->field('exhibitionSummary', 'Summary', PlainText::class, TranslationMethod::Site);
        $description = $this->field('exhibitionDescription', 'Description', PlainText::class, TranslationMethod::Site, [
            'multiline' => true,
            'initialRows' => 4,
        ]);
        $venue = $this->field('exhibitionVenue', 'Venue', PlainText::class, TranslationMethod::None);
        $sectionText = $this->field('exhibitionSectionText', 'Text', PlainText::class, TranslationMethod::Site, [
            'multiline' => true,
            'initialRows' => 2,
        ]);
        $sectionType = $this->entryType('exhibitionSection', 'Exhibition section', [$sectionText]);
        $sections = $this->field('exhibitionSections', 'Sections', Matrix::class, TranslationMethod::Site, [
            'entryTypes' => [$sectionType->id],
            'viewMode' => Matrix::VIEW_MODE_BLOCKS,
            'propagationMethod' => 'all',
            'enableVersioning' => true,
            'createButtonLabel' => 'Add a section',
        ]);
        // A content tab for the Matrix field and a sidebar tab, which the Compare screen shows alongside the others.
        $exhibitionType = $this->entryType('exhibition', 'Exhibition', [$summary, $description], [
            ['name' => 'Sections', 'fields' => [$sections]],
            ['name' => 'Visit', 'type' => 'sidebar', 'fields' => [$venue]],
        ]);

        $section = Sections::getSectionByHandle('exhibitions');

        if (! $section) {
            $section = new Section([
                'name' => 'Exhibitions',
                'handle' => 'exhibitions',
                'type' => SectionType::Channel,
                'enableVersioning' => true,
                'entryTypes' => [$exhibitionType],
                'siteSettings' => collect([$primary, $french, $german])
                    ->mapWithKeys(fn (Site $site): array => [$site->id => new SectionSiteSettings([
                        'siteId' => $site->id,
                        'hasUrls' => false,
                    ])])
                    ->all(),
            ]);

            if (! Sections::saveSection($section)) {
                throw new RuntimeException('Failed to create the Exhibitions section.');
            }
        }

        if (Entry::find()->sectionId($section->id)->slug('light-and-line')->status(null)->exists()) {
            return;
        }

        $entry = new Entry([
            'siteId' => $primary->id,
            'sectionId' => $section->id,
            'typeId' => $exhibitionType->id,
            'title' => 'Light and Line',
            'slug' => 'light-and-line',
            'postDate' => now()->subDay(),
        ]);
        $entry->setFieldValues([
            'exhibitionSummary' => 'Twelve works on paper that treat drawing as a way of catching light.',
            'exhibitionDescription' => "The exhibition brings together drawings made over four decades.\nEach room follows one artist’s line from first sketch to finished sheet.",
            'exhibitionVenue' => 'East Gallery, Main Campus',
        ]);
        $entry->setFieldValueFromRequest('exhibitionSections', $this->newSections([
            'Introduction' => 'Why drawing, and why light? A short room about where the show began.',
            'Early sketches' => 'Notebook pages from the 1980s, shown open for the first time.',
            'Large-scale works' => 'Three wall drawings made on site for this exhibition.',
            'Visiting' => 'Open Tuesday to Sunday, 10 to 6. Free entry on the first Friday of each month.',
        ]));
        $this->save($entry);

        // French: translated, except for the description, which is still to do.
        $translation = $this->inSite($entry, $french);
        $translation->title = 'Lumière et ligne';
        $translation->setFieldValues([
            'exhibitionSummary' => 'Douze œuvres sur papier qui font du dessin une manière de saisir la lumière.',
            'exhibitionDescription' => '',
        ]);
        $this->save($translation);

        foreach ($translation->getFieldValue('exhibitionSections')->status(null)->all() as $index => $nested) {
            $nested->title = ['Introduction', 'Premières esquisses', 'Œuvres grand format', 'Visite'][$index] ?? $nested->title;
            $nested->setFieldValue('exhibitionSectionText', [
                'Pourquoi le dessin, et pourquoi la lumière ? Une petite salle sur l’origine de l’exposition.',
                'Des pages de carnets des années 1980, présentées ouvertes pour la première fois.',
                'Trois dessins muraux réalisés sur place pour l’exposition.',
                'Ouvert du mardi au dimanche, de 10 h à 18 h. Entrée libre le premier vendredi du mois.',
            ][$index] ?? '');
            $this->save($nested);
        }

        // A draft that moves the visiting information to the top, drops the introduction, rewords the large-scale works,
        // and adds a section.
        $author = User::find()->admin()->one();
        $draft = app(Drafts::class)->createDraft($entry, $author?->id, 'Visitor-first order');
        $existing = $draft->getFieldValue('exhibitionSections')->status(null)->ids();
        [, $sketches, $largeScale, $visiting] = $existing;
        $draft->setFieldValueFromRequest('exhibitionSections', [
            'sortOrder' => [$visiting, 'new1', $largeScale, $sketches],
            'entries' => [
                $visiting => [],
                $largeScale => ['type' => 'exhibitionSection', 'fields' => ['exhibitionSectionText' => 'Three wall drawings made on site, each over four metres long.']],
                $sketches => [],
                ...$this->newSections(['Audio guide' => 'Borrow an audio guide at the front desk, in English, French, or German.'], 'new')['entries'],
            ],
        ]);
        $this->save($draft);
    }

    private function site(string $handle, string $name, string $language, Site $primary): Site
    {
        if ($site = Sites::getSiteByHandle($handle, withDisabled: true)) {
            return $site;
        }

        $site = new Site([
            'groupId' => $primary->groupId,
            'name' => $name,
            'handle' => $handle,
            'language' => $language,
            'baseUrl' => rtrim((string) $primary->baseUrl, '/').'/'.Str::before($language, '-'),
            'hasUrls' => true,
        ]);

        if (! Sites::saveSite($site)) {
            throw new RuntimeException("Failed to create the {$name} site.");
        }

        return $site;
    }

    /**
     * @param  class-string<FieldInterface>  $type
     * @param  array<string, mixed>  $settings
     */
    private function field(string $handle, string $name, string $type, TranslationMethod $translationMethod, array $settings = []): FieldInterface
    {
        if ($field = Fields::getFieldByHandle($handle)) {
            return $field;
        }

        $field = Fields::createField([
            'type' => $type,
            'name' => $name,
            'handle' => $handle,
            'searchable' => true,
            'translationMethod' => $translationMethod->value,
            'settings' => $settings,
        ]);

        if (! Fields::saveField($field)) {
            throw new RuntimeException("Failed to create the {$name} field.");
        }

        Fields::refreshFields();

        return $field;
    }

    /**
     * @param  list<FieldInterface>  $fields  The fields in the Content tab, after the title.
     * @param  list<array{name: string, type?: string, fields: list<FieldInterface>}>  $tabs  The tabs after it.
     */
    private function entryType(string $handle, string $name, array $fields, array $tabs = []): EntryType
    {
        if ($entryType = EntryTypes::getEntryTypeByHandle($handle)) {
            return $entryType;
        }

        $layout = FieldLayout::create([
            'uid' => Str::uuid()->toString(),
            'type' => Entry::class,
            'config' => ['tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Content',
                    'elements' => [
                        ['uid' => Str::uuid()->toString(), 'type' => EntryTitleField::class, 'required' => true],
                        ...$this->fieldElements($fields),
                    ],
                ],
                ...array_map(fn (array $tab): array => [
                    'uid' => Str::uuid()->toString(),
                    'name' => $tab['name'],
                    'type' => $tab['type'] ?? 'content',
                    'elements' => $this->fieldElements($tab['fields']),
                ], $tabs),
            ]],
        ]);
        $entryType = new EntryType([
            'name' => $name,
            'handle' => $handle,
            'fieldLayoutId' => $layout->id,
        ]);

        if (! EntryTypes::saveEntryType($entryType)) {
            throw new RuntimeException("Failed to create the {$name} entry type.");
        }

        return $entryType;
    }

    /**
     * @param  list<FieldInterface>  $fields
     * @return list<array<string, string>>
     */
    private function fieldElements(array $fields): array
    {
        return array_map(fn (FieldInterface $field): array => [
            'uid' => Str::uuid()->toString(),
            'type' => CustomField::class,
            'fieldUid' => $field->uid,
        ], $fields);
    }

    /**
     * @param  array<string, string>  $sections  Text by title
     * @return array{entries: array<string, array<string, mixed>>, sortOrder: list<string>}
     */
    private function newSections(array $sections, string $prefix = 'new'): array
    {
        $entries = [];
        $index = 0;

        foreach ($sections as $title => $text) {
            $entries[$prefix.++$index] = [
                'type' => 'exhibitionSection',
                'title' => $title,
                'fields' => ['exhibitionSectionText' => $text],
            ];
        }

        return ['entries' => $entries, 'sortOrder' => array_keys($entries)];
    }

    private function inSite(Entry $entry, Site $site): Entry
    {
        return Entry::find()->id($entry->id)->siteId($site->id)->status(null)->one()
            ?? throw new RuntimeException("{$entry->title} wasn’t propagated to {$site->name}.");
    }

    private function save(Entry $entry): void
    {
        if (! Elements::saveElement($entry)) {
            throw new RuntimeException("Failed to save {$entry->title}: ".Json::encode($entry->errors()->all()));
        }
    }
}
