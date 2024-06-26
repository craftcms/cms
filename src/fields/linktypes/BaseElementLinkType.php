<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\linktypes;

use Craft;
use craft\base\ElementInterface;
use craft\fields\Link;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\services\ElementSources;

/**
 * Base element link type.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
abstract class BaseElementLinkType extends BaseLinkType
{
    /**
     * Returns the element type this link type is for.
     *
     * @return ElementInterface|string
     * @phpstan-return class-string<ElementInterface>
     */
    abstract protected static function elementType(): string;

    public static function id(): string
    {
        return sprintf('element-%s', static::elementType()::refHandle());
    }

    public static function label(): string
    {
        return static::elementType()::displayName();
    }

    public static function supports(string $value): bool
    {
        return (bool)preg_match(sprintf('/^\{%s:(\d+)(@(\d+))?:url\}$/', static::elementType()::refHandle()), $value);
    }

    public static function render(string $value): string
    {
        return Craft::$app->getElements()->parseRefs($value);
    }

    public static function inputHtml(Link $field, ?string $value, string $containerId): string
    {
        $elements = [];

        if ($value && preg_match(sprintf('/^\{%s:(\d+)(?:@(\d+))?:url\}$/', static::elementType()::refHandle()), $value, $match)) {
            $id = $match[1];
            $siteId = $match[2] ?? null;
            $query = static::elementType()::find()
                ->id((int)$id)
                ->status(null)
                ->drafts(null)
                ->revisions(null);

            if ($siteId) {
                $query->siteId((int)$siteId);
            } else {
                $query
                    ->site('*')
                    ->unique()
                    ->preferSites([Craft::$app->getSites()->getCurrentSite()->id]);
            }

            $element = $query->one();
            if ($element) {
                $elements[] = $element;
            }
        }

        $id = sprintf('elementselect%s', mt_rand());

        $view = Craft::$app->getView();
        $view->registerJsWithVars(fn($id, $refHandle) => <<<JS
(() => {
  const container = $('#' + $id);
  const input = container.next('input');
  const elementSelect = container.data('elementSelect');
  const refHandle = $refHandle;
  elementSelect.on('selectElements', (ev) => {
    const element = ev.elements[0];
    input.val(`{\${refHandle}:\${element.id}@\${element.siteId}:url}`);
  });
  elementSelect.on('removeElements', () => {
    input.val('');
  });
})();
JS, [
            'id' => $view->namespaceInputId($id),
            'refHandle' => static::elementType()::refHandle(),
        ]);

        return
            Cp::elementSelectHtml([
                'id' => $id,
                'elementType' => static::elementType(),
                'limit' => 1,
                'single' => true,
                'elements' => $elements,
                'sources' => array_merge(static::selectionSources(), self::customSources()),
                'criteria' => static::selectionCriteria(),
            ]) .
            Html::hiddenInput('value', $value);
    }

    protected static function selectionSources(): array
    {
        return [];
    }

    private static function customSources(): array
    {
        $customSources = [];
        $elementSources = Craft::$app->getElementSources()->getSources(static::elementType(), 'modal');
        foreach ($elementSources as $elementSource) {
            if ($elementSource['type'] === ElementSources::TYPE_CUSTOM && isset($elementSource['key'])) {
                $customSources[] = $elementSource['key'];
            }
        }
        return $customSources;
    }

    protected static function selectionCriteria(): array
    {
        return [
            'uri' => 'not :empty:',
        ];
    }

    public static function validate(string $value, ?string &$error = null): bool
    {
        return true;
    }
}
