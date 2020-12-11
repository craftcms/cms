<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;


use Craft;
use craft\base\ElementInterface;
use craft\elements\GlobalSet;

/**
 * Class GlobalSetFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
abstract class GlobalSetFixture extends BaseElementFixture
{
    /**
     * @var array content store
     */
    private $_contentStore = [];

    /**
     * @inheritdoc
     */
    public function load()
    {
        parent::load();

        $globals = Craft::$app->getGlobals();
        $globals->reset();

        // Add the content now that the globals exist and have fields.
        foreach ($this->_contentStore as $handle => $fieldValues) {
            $globalSet = $globals->getSetByHandle($handle) ?? GlobalSet::find()->trashed()->handle($handle)->one();

            if (!$globalSet) {
                continue;
            }

            foreach ($fieldValues as $field => $value) {
                $globalSet->{$field} = $value;
            }

            Craft::$app->getElements()->saveElement($globalSet);
        }

        $globals->reset();
    }

    /**
     * @inheritDoc
     */
    protected function populateElement(ElementInterface $element, array $attributes): void
    {
        $deferredAttributes = [];

        // Store all the content for later.
        foreach ($attributes as $attribute => $value) {
            if (substr($attribute, 0, 6) === 'field:') {
                $deferredAttributes[$attribute] = $value;
            }
        }

        $this->_contentStore[$attributes['handle']] = $deferredAttributes;
        parent::populateElement($element, $attributes);
    }

    /**
     * @inheritdoc
     */
    public function unload()
    {
        parent::unload();
        Craft::$app->getGlobals()->reset();
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new GlobalSet();
    }

    /**
     * @inheritdoc
     */
    protected function saveElement(ElementInterface $element): bool
    {
        return Craft::$app->getGlobals()->saveSet($element);
    }
}
