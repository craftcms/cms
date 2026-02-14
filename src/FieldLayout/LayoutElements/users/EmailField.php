<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\LayoutElements\users;

use Craft;
use craft\base\ElementInterface;
use CraftCms\Cms\Edition;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

class EmailField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'email';

    public ?int $maxlength = 255;

    public bool $autofocus = true;

    public function __construct($config = [])
    {
        // We didn't start removing autofocus from fields() until 3.5.6
        parent::__construct(Arr::except($config, [
            'mandatory',
            'attribute',
            'translatable',
            'maxlength',
            'required',
            'autofocus',
            'warning',
        ]));
    }

    #[Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), [
            'mandatory',
            'attribute',
            'translatable',
            'maxlength',
            'required',
            'autofocus',
            'warning',
        ]);
    }

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return t('Email');
    }

    #[Override]
    protected function warning(?ElementInterface $element = null, bool $static = false): ?string
    {
        /** @var User $element */
        if (
            Edition::get()->value >= Edition::Pro->value &&
            ProjectConfig::get('users.requireEmailVerification') &&
            ! $element->getIsDraft() &&
            ! Gate::check('administrateUsers')
        ) {
            return t('New email addresses must be verified before taking effect.');
        }

        return null;
    }

    #[Override]
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element) {
            if (! $element instanceof User) {
                throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
            }

            if (
                ! $element->getIsCurrent() &&
                ! $element->getIsDraft() &&
                ! Gate::check('administrateUsers')
            ) {
                return null;
            }

            Craft::$app->getView()->registerJsWithVars(fn ($id) => <<<JS
new Craft.ElevatedSessionForm($('#' + $id).closest('form'), ['#' + $id]);
JS, [
                InputNamespace::namespaceInputId($this->attribute),
            ]);
        }

        return parent::inputHtml($element, $static);
    }

    #[Override]
    protected function inputAttributes(?ElementInterface $element = null, bool $static = false): array
    {
        if (! $element instanceof User) {
            throw new InvalidArgumentException(sprintf('%s can only be used in user field layouts.', self::class));
        }

        return [
            'autocomplete' => $element->getIsCurrent() ? 'email' : 'off',
            'data' => ['lpignore' => 'true'],
        ];
    }

    #[Override]
    protected function fieldErrors(?ElementInterface $element = null): array
    {
        if (! $element) {
            return [];
        }

        return array_merge($element->errors()->get('email'), $element->errors()->get('unverifiedEmail'));
    }
}
