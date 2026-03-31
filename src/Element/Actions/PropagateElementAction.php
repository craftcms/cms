<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Models\ElementSiteSettings;
use CraftCms\Cms\Shared\Exceptions\OperationAbortedException;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use function CraftCms\Cms\t;

/** @internal */
readonly class PropagateElementAction
{
    public function __construct(
        private Elements $elements,
        private Sites $sites,
        private SaveElementAction $saveElementAction,
    ) {}

    public function handle(
        ElementInterface $element,
        array $supportedSites,
        int $siteId,
        ElementInterface|false|null &$siteElement = null,
        bool $crossSiteValidate = false,
        bool $saveContent = true,
        ?ElementSiteSettings &$siteSettingsRecord = null,
    ): bool {
        // Make sure the element actually supports the site it's being saved in
        if (! isset($supportedSites[$siteId])) {
            throw new UnsupportedSiteException($element, $siteId, 'Attempting to propagate an element to an unsupported site.');
        }

        $siteInfo = $supportedSites[$siteId];

        // Try to fetch the element in this site
        if ($siteElement === null && $element->id) {
            /** @phpstan-ignore-next-line */
            $siteElement = $this->elements->getElementById($element->id, $element::class, $siteInfo['siteId']);
        } elseif (! $siteElement) {
            /** @phpstan-ignore-next-line */
            $siteElement = null;
        }

        // If it doesn't exist yet, just clone the initial site
        if ($siteElement === null) {
            $siteElement = clone $element;
            $siteElement->siteId = $siteInfo['siteId'];
            $siteElement->siteSettingsId = null;
            $siteElement->setEnabledForSite($siteInfo['enabledByDefault']);
            // set isNewForSite to true unless we're reverting content from a revision
            // in which case, it's possible that the canonical element exists for the site already,
            // but didn't back when the revision was created.
            // (see https://github.com/craftcms/cms/issues/15679)
            $siteElement->isNewForSite = ! $siteElement->duplicateOf?->getIsRevision();

            // Keep track of this new site ID
            $element->newSiteIds[] = $siteInfo['siteId'];
        } elseif ($element->propagateAll) {
            $oldSiteElement = $siteElement;
            $siteElement = clone $element;
            $siteElement->siteId = $oldSiteElement->siteId;
            $siteElement->setEnabledForSite($oldSiteElement->getEnabledForSite());
            $siteElement->uri = $oldSiteElement->uri;
        } else {
            $siteElement->enabled = $element->enabled;
            $siteElement->resaving = $element->resaving;
        }

        // Does the main site's element specify a status for this site?
        $enabledForSite = $element->getEnabledForSite($siteElement->siteId);
        if ($enabledForSite !== null) {
            $siteElement->setEnabledForSite($enabledForSite);
        }

        // Copy the timestamps
        $siteElement->dateCreated = $element->dateCreated;
        $siteElement->dateUpdated = $element->dateUpdated;

        // Copy the title value?
        if (
            $element::hasTitles() &&
            (
                $siteElement->getTitleTranslationKey() === $element->getTitleTranslationKey() ||
                ($element->propagateRequired && empty($siteElement->title))
            )
        ) {
            $siteElement->title = $element->title;
        }

        // Copy the slug value?
        if (
            $element->slug !== null &&
            (
                $siteElement->getSlugTranslationKey() === $element->getSlugTranslationKey() ||
                ($element->propagateRequired && empty($siteElement->slug))
            )
        ) {
            $siteElement->slug = $element->slug;
        }

        // Ensure the uri is properly localized
        // see https://github.com/craftcms/cms/issues/13812 for more details
        if (
            $element::hasUris() &&
            (
                $siteElement->isNewForSite ||
                in_array('uri', $element->getDirtyAttributes()) ||
                $element->resaving
            )
        ) {
            // Set a unique URI on the site clone
            try {
                $this->elements->setElementUri($siteElement);
            } catch (OperationAbortedException) {
                // carry on
            }
        }

        // Save it
        $siteElement->setScenario(Element::SCENARIO_ESSENTIALS);

        // validate element against "live" scenario across all sites, if element is enabled for the site
        if (
            ($crossSiteValidate || $element->propagateRequired) &&
            $siteElement->enabled &&
            $siteElement->getEnabledForSite()
        ) {
            $siteElement->setScenario(Element::SCENARIO_LIVE);
        }

        // Copy the dirty attributes (except title, slug and uri, which may be translatable)
        $siteElement->setDirtyAttributes(array_filter($element->getDirtyAttributes(),
            fn (string $attribute): bool => $attribute !== 'title' && $attribute !== 'slug'));

        if ($saveContent) {
            // Copy any non-translatable field values
            if ($siteElement->isNewForSite) {
                // Copy all the field values
                $siteElement->setFieldValues($element->getFieldValues());
            } else {
                $fieldLayout = $element->getFieldLayout();

                if ($fieldLayout !== null) {
                    foreach ($fieldLayout->getCustomFields() as $field) {
                        if (
                            $element->propagateAll ||
                            // If propagateRequired is set, is the field value invalid on the propagated site element?
                            (
                                $element->propagateRequired &&
                                $field->layoutElement->required &&
                                $field->isValueEmpty($siteElement->getFieldValue($field->handle), $siteElement)
                            ) ||
                            // Has this field changed, and does it produce the same translation key as it did for the initial element?
                            (
                                $element->isFieldDirty($field->handle) &&
                                $field->getTranslationKey($siteElement) === $field->getTranslationKey($element)
                            )
                        ) {
                            $field->propagateValue($element, $siteElement);
                        }
                    }
                }
            }
        }

        $siteElement->propagating = true;
        $siteElement->propagatingFrom = $element;

        $success = $this->saveElementAction->handle(
            $siteElement,
            $crossSiteValidate,
            false,
            supportedSites: $supportedSites,
            saveContent: $saveContent,
            siteSettingsRecord: $siteSettingsRecord,
        );

        if ($success) {
            return true;
        }

        // if the element we're trying to save has validation errors, notify original element about them
        if ($siteElement->errors()->isNotEmpty()) {
            return $this->crossSiteValidationErrors($siteElement, $element);
        }

        // Log the errors
        $error = 'Couldn’t propagate element to other site due to validation errors:';

        foreach ($siteElement->errors()->all() as $attributeError) {
            $error .= "\n- ".$attributeError;
        }

        Log::error($error);

        throw new Exception('Couldn’t propagate element to other site.');
    }

    private function crossSiteValidationErrors(
        ElementInterface $siteElement,
        ElementInterface $element,
    ): bool {
        // get site we're propagating to
        $propagateToSite = $this->sites->getSiteById($siteElement->siteId);

        /** @var ?User $user */
        $user = Auth::user();
        $message = t('Validation errors for site: “{siteName}“', [
            'siteName' => $propagateToSite?->getName(),
        ]);

        // check user can edit this element for the site that throws validation error on propagation
        if ($user &&
            $this->sites->isMultiSite() &&
            $user->can("editSite:{$propagateToSite?->uid}") &&
            $siteElement->canSave($user)
        ) {
            $queryParams = Arr::except(request()->query(), 'site');
            $url = Url::url($siteElement->getCpEditUrl(), $queryParams + ['prevalidate' => 1]);
            $message = Html::beginTag('a', [
                'href' => $url,
                'class' => 'cross-site-validate',
                'target' => '_blank',
            ]).
            $message.
            Html::tag('span', '', [
                'data-icon' => 'external',
                'aria-label' => t('Open in a new tab'),
                'role' => 'img',
            ]).
            Html::endTag('a');
        }

        $element->errors()->add('global', $message);

        return false;
    }
}
