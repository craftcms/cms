<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Responses;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\FieldLayout\LayoutElements\BaseField;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Facades\Drafts;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class ElementResponse
{
    use RespondsWithFlash;

    /** @param array<string, mixed> $data */
    public function success(ElementInterface $element, string $message, array $data = [], bool $supportsAddAnother = false): Response
    {
        // Don't call asModelSuccess() here so we can avoid including custom fields in the element data
        $data += [
            'modelName' => 'element',
            'element' => $element->toArray($element->attributes()),
        ];

        $response = $this->asSuccess($message, $data, $this->getPostedRedirectUrl($element), [
            'details' => ! $element->dateDeleted
                ? app(ElementHtml::class)->elementChipHtml($element, ['hyperlink' => true])
                : null,
        ]);

        if ($supportsAddAnother && request()->boolean('addAnother')) {
            $user = request()->craftUser();
            $newElement = $element->createAnother();

            if (! $newElement || ! Gate::check('save', $newElement)) {
                abort(500, 'Unable to create a new element.');
            }

            if (! $newElement->slug) {
                $newElement->slug = ElementHelper::tempSlug();
            }

            $newElement->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);

            if (! Drafts::saveElementAsDraft($newElement, $user->getCraftUserId(), null, null, false)) {
                abort(500, sprintf('Unable to create a new element: %s', implode(', ', $element->errors()->all())));
            }

            $url = $newElement->getCpEditUrl();

            if ($url) {
                $url = Url::urlWithParams($url, ['fresh' => 1]);
            } else {
                $url = Url::actionUrl('elements/edit', [
                    'draftId' => $newElement->draftId,
                    'siteId' => $newElement->siteId,
                    'fresh' => 1,
                ]);
            }

            if ($returnUrl = request()->input('returnUrl')) {
                $url = Url::urlWithParams($url, ['returnUrl' => $returnUrl]);
            }

            return redirect($url);
        }

        return $response;
    }

    public function failure(ElementInterface $element, string $message): Response
    {
        $data = [
            'modelName' => 'element',
            'element' => $element->toArray($element->attributes()),
            // The legacy editor and the slideouts apply errors by the raw
            // attribute name, so the JSON branch keeps them as validated.
            'errors' => $element->errors()->getMessages(),
            'errorSummary' => $this->errorSummary($element),
            'invalidNestedElementIds' => $element->getInvalidNestedElementIds(),
        ];

        // The Inertia editor matches errors to Form Controls by path, which is
        // the posted input name rather than the validated attribute.
        if (! request()->expectsJson()) {
            $data['errors'] = $element->formErrors();
        }

        return $this->asFailure($message, $data);
    }

    public function applyDraftFailure(ElementInterface $element): Response
    {
        $message = match (true) {
            $element->getIsUnpublishedDraft() => mb_ucfirst(t('Couldn’t create {type}.', [
                'type' => $element::lowerDisplayName(),
            ])),
            $element->isProvisionalDraft => mb_ucfirst(t('Couldn’t save {type}.', [
                'type' => $element::lowerDisplayName(),
            ])),
            default => t('Couldn’t apply draft.'),
        };

        return $this->failure($element, $message);
    }

    public function errorSummary(ElementInterface $element): string
    {
        $html = '';

        if ($element->errors()->isNotEmpty()) {
            $allErrors = $element->errors()->getMessages();
            $allKeys = array_keys($allErrors);

            // only show "top-level" errors
            // if you e.g. have an assets field which is set to validate related assets,
            // you should only see the top-level "Fix validation errors on the related asset" error
            // and not the details of what's wrong with the selected asset;
            foreach ($allKeys as $key) {
                $dotPos = strrpos((string) $key, '.');
                $bracketPos = strrpos((string) $key, '[');

                if ($dotPos === false && $bracketPos === false) {
                    continue;
                }

                $lastNestedKey = $key;

                if ($dotPos !== false) {
                    $lastNestedKey = substr_replace($lastNestedKey, '', $dotPos);
                }

                $bracketPos = strrpos((string) $lastNestedKey, '[');

                if ($bracketPos !== false) {
                    $lastNestedKey = substr_replace($lastNestedKey, '', $bracketPos);
                }

                if (! empty($lastNestedKey) && in_array($lastNestedKey, $allKeys)) {
                    unset($allErrors[$key]);
                }
            }
            $errorsList = [];
            $tabs = $element->getFieldLayout()->getTabs();
            foreach ($allErrors as $key => $errors) {
                foreach ($errors as $error) {
                    // this is true in case of e.g. cross site validation error
                    if (preg_match('/^\s?\<a /', (string) $error)) {
                        $errorItem = Html::beginTag('li');
                        $errorItem .= $error;
                        $errorItem .= Html::endTag('li');
                    } else {
                        // get tab uid for this error
                        $tabUid = null;
                        $bracketPos = strpos((string) $key, '[');
                        $fieldKey = substr((string) $key, 0, $bracketPos ?: null);
                        foreach ($tabs as $tab) {
                            foreach ($tab->getElements() as $layoutElement) {
                                if ($layoutElement instanceof BaseField && $layoutElement->attribute() === $fieldKey) {
                                    $tabUid = $tab->uid;
                                    break 2;
                                }
                            }
                        }

                        // If the error is for a recursively-nested Matrix field,
                        // manipulate the key to only reference the nested Matrix field, entry and inner field
                        // Before: foo[<uuid>].bar[<uuid>].baz
                        // After:  bar[<uuid>].baz
                        if (substr_count((string) $key, '.') > 1) {
                            $keyParts = explode('.', (string) $key);
                            if (preg_match(sprintf('/\[%s\]$/', Str::uuidPattern()), $keyParts[count($keyParts) - 3])) {
                                $key = implode('.', array_slice($keyParts, -2));
                            }
                        }

                        $errorItem = null;
                        if ($error !== null) {
                            $error = Markdown::parseParagraph(htmlspecialchars($error));
                            $errorItem = Html::beginTag('li');
                            $errorItem .= Html::a(t($error), '#', [
                                'data' => [
                                    'field-error-key' => $key,
                                    'layout-tab' => $tabUid,
                                ],
                            ]);
                            $errorItem .= Html::endTag('li');
                        }
                    }

                    if ($errorItem !== null) {
                        $errorsList[] = $errorItem;
                    }
                }
            }

            if (! empty($errorsList)) {
                $heading = t('Found {num, number} {num, plural, =1{error} other{errors}}', [
                    'num' => count($errorsList),
                ]);

                $html = Html::beginTag('div', [
                    'class' => ['error-summary'],
                    'tabindex' => '-1',
                ]).
                    Html::beginTag('div').
                    Html::tag('span', '', [
                        'class' => 'notification-icon',
                        'data-icon' => 'alert',
                        'aria-label' => t('Error'),
                        'role' => 'img',
                    ]).
                    Html::tag('h2', $heading).
                    Html::endTag('div').
                    Html::beginTag('ul', [
                        'class' => ['errors'],
                    ]).
                    implode('', $errorsList).
                    Html::endTag('ul').
                    Html::endTag('div');
            }
        }

        return $html;
    }
}
