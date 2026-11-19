<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements;

use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\ElementSourceUi;
use CraftCms\Cms\Element\PersonalElementSources;
use CraftCms\Cms\Http\Controllers\Elements\Concerns\SavesSourceSettings;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

/**
 * Saves the sources a user keeps to themselves, and shares one with everyone.
 *
 * @since 6.0.0
 */
readonly class PersonalElementSourcesController
{
    use RespondsWithFlash;
    use SavesSourceSettings;

    public function __construct(
        private PersonalElementSources $personalSources,
        private ElementSources $elementSources,
        private Navigation $navigation,
    ) {}

    /** Saves an index's view as a new source of the user's own. */
    public function store(ElementIndexRequest $request): Response
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string'],
            ...$this->sourceSettingRules(),
        ]);

        $from = $this->elementSources->findSource($elementType, $request->string('source')->value());
        abort_if($from === null, 404);

        $condition = $request->array('condition');
        $source = $this->personalSources->add($this->user(), $elementType, array_filter([
            'label' => trim($request->string('label')->value()),
            'baseSource' => $this->baseSourceKey($elementType, $from),
            'page' => $from['page'] ?? null,
            ...$this->postedViewSettings($request),
            // Even without filters: the index builds a custom source's query
            // from its condition.
            'condition' => $this->sourceCondition($condition !== [] ? $condition : $elementType::createCondition()->getConfig()),
        ], fn (mixed $value) => $value !== null));

        return $this->asSuccess(
            t('Saved “{source}”.', ['source' => $source['label']]),
            data: ['sourceKey' => $source['key']],
            redirect: $this->navigation->sourceUrl($elementType, $source['key']),
        );
    }

    /** Saves an index's view, or a new name, onto one of the user's sources. */
    public function update(ElementIndexRequest $request): Response
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $request->validate([
            'source' => ['required', 'string'],
            'label' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'string'],
            ...$this->sourceSettingRules(),
        ]);

        [$user, $key, $source] = $this->ownSource($request, $elementType);
        $settings = $this->postedViewSettings($request, $source);

        if ($request->filled('label')) {
            $settings['label'] = trim($request->string('label')->value());
        }

        if ($request->filled('page')) {
            $settings['page'] = $request->string('page')->value();
        }

        if ($request->has('condition')) {
            $settings['condition'] = $this->sourceCondition($request->array('condition'));
        }

        $this->personalSources->update($user, $elementType, $key, $settings);

        return $this->asSuccess(
            t('Saved changes to “{source}”.', ['source' => $settings['label'] ?? $source['label'] ?? '']),
            redirect: $this->navigation->sourceUrl($elementType, $key),
        );
    }

    /** Deletes one of the user's sources. */
    public function destroy(ElementIndexRequest $request): Response
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $request->validate(['source' => ['required', 'string']]);

        [$user, $key, $source] = $this->ownSource($request, $elementType);
        $this->personalSources->delete($user, $elementType, $key);

        return $this->asSuccess(
            t('Deleted “{source}”.', ['source' => $source['label'] ?? '']),
            redirect: $this->landingUrl($elementType, $source),
        );
    }

    /**
     * Moves one of the user's sources into project config, where everyone can
     * see it. Its filters have to be ones project config can hold.
     */
    public function share(ElementIndexRequest $request, ElementSourceUi $sourceUi): Response
    {
        /** @var class-string<ElementInterface> $elementType */
        $elementType = $request->elementType();
        $request->validate([
            'source' => ['required', 'string'],
            // Limits it to these groups, rather than everyone.
            'userGroups' => ['nullable', 'array'],
            'userGroups.*' => ['string'],
        ]);

        [$user, $key, $source] = $this->ownSource($request, $elementType);
        ['condition' => $condition, 'dropped' => $dropped] =
            $sourceUi->storableCondition($elementType, $source['condition'] ?? $elementType::createCondition()->getConfig());

        if ($dropped !== []) {
            throw ValidationException::withMessages([
                'source' => t('These filters can’t be shared: {rules}. Remove them first.', [
                    'rules' => implode(', ', array_unique($dropped)),
                ]),
            ]);
        }

        $sharedKey = 'custom:'.Str::uuid()->toString();
        $this->elementSources->addSource($elementType, [
            ...$source,
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => $sharedKey,
            'condition' => $this->sourceCondition($condition),
            ...($request->filled('userGroups') ? ['userGroups' => array_values($request->array('userGroups'))] : []),
        ]);
        $this->personalSources->delete($user, $elementType, $key);

        return $this->asSuccess(
            t('“{source}” is now visible to everyone.', ['source' => $source['label'] ?? '']),
            data: ['sourceKey' => $sharedKey],
            redirect: $this->navigation->sourceUrl($elementType, $sharedKey),
        );
    }

    private function user(): CraftUser
    {
        $user = currentUser();
        abort_if($user === null, 403);

        return $user;
    }

    /**
     * The posted source, which has to be one of the user's own.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array{0: CraftUser, 1: string, 2: array<string, mixed>}
     */
    private function ownSource(ElementIndexRequest $request, string $elementType): array
    {
        $user = $this->user();
        $key = $request->string('source')->value();
        $source = $this->personalSources->find($user, $elementType, $key);

        abort_if($source === null, 404, t('That source can’t be found.'));

        return [$user, $key, $source];
    }

    /**
     * The built-in source a new source narrows: the one it was saved from, or
     * the one that source narrows in turn. A custom source that narrows none
     * falls back to the element type's first, so a user never sees more than
     * a built-in source would show them.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $from
     */
    private function baseSourceKey(string $elementType, array $from): ?string
    {
        if (($from['type'] ?? null) === ElementSources::TYPE_NATIVE) {
            return explode('/', (string) ($from['keyPath'] ?? $from['key']))[0];
        }

        return $from['baseSource']
            ?? $this->elementSources->getSources($elementType)->firstWhere('type', ElementSources::TYPE_NATIVE)['key']
            ?? null;
    }

    /**
     * Where to go once a source is gone: the one it narrowed.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $source
     */
    private function landingUrl(string $elementType, array $source): ?string
    {
        $key = $source['baseSource']
            ?? $this->elementSources->getSources($elementType)->firstWhere('type', ElementSources::TYPE_NATIVE)['key']
            ?? null;

        return $key !== null ? $this->navigation->sourceUrl($elementType, $key) : null;
    }
}
