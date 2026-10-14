<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Elements\Concerns;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

use function CraftCms\Cms\t;

trait EditsElement
{
    protected readonly ElementRequest $request;

    /** @return array{string, string} */
    protected function editElementTitles(ElementInterface $element): array
    {
        $title = match (true) {
            $element::hasTitles() && $element->title !== null && $element->title !== '' => $element->title,
            ! $element->id || $element->getIsUnpublishedDraft() => t('Create a new {type}', [
                'type' => $element::lowerDisplayName(),
            ]),
            default => $element->getUiLabel(),
        };

        $docTitle = $element->getUiLabel();

        if ($element->getIsDraft() && ! $element->getIsUnpublishedDraft()) {
            if ($element->isProvisionalDraft) {
                $docTitle .= ' — '.t('Edited');
            } else {
                $docTitle .= " ($element->draftName)";
            }
        } elseif ($element->getIsRevision()) {
            $docTitle .= ' ('.$element->getRevisionLabel().')';
        }

        // Include site name if localized
        if ($element::isLocalized() && Sites::isMultiSite()) {
            $docTitle .= sprintf(' - %s', $element->getSite()->getUiLabel());
        }

        return [$docTitle, $title];
    }

    /**
     * Where saving the element should send the user: the `returnUrl` the editor
     * was opened with, or else the element's post-edit URL.
     */
    protected function postEditRedirectUrl(ElementInterface $element): string
    {
        $redirectUrl = $this->request->query('returnUrl');

        if (! $redirectUrl) {
            return ElementHelper::postEditUrl($element);
        }

        // only require the URL to be hashed if it contains Twig code
        try {
            $redirectUrl = Crypt::decrypt($redirectUrl);
        } catch (DecryptException) {
            if (str_contains($redirectUrl, '{')) {
                abort(400, "Invalid returnUrl param: $redirectUrl");
            }
        }

        // swap the QS placeholder with a `?`
        // (see https://github.com/craftcms/cms/issues/18923)
        return str_replace(':QS:', '?', $redirectUrl);
    }
}
