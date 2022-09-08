<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\events\DefineLastNamePrefixesEvent;
use craft\events\DefineNameSalutationsEvent;
use craft\events\DefineNameSuffixesEvent;
use TheIconic\NameParser\Language\English;
use yii\base\Event;

/**
 * Unified Name Parser language file
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.3.0
 */
class NameParserLanguage extends English
{
    public const EVENT_DEFINE_SUFFIXES = 'defineSuffixes';
    public const EVENT_DEFINE_SALUTATIONS = 'defineSalutations';
    public const EVENT_DEFINE_LASTNAME_PREFIXES = 'defineLastNamePrefixes';

    public function getLastnamePrefixes(): array
    {
        $event = new DefineLastNamePrefixesEvent([
            'lastNamePrefixes' => parent::getLastnamePrefixes(),
        ]);

        Event::trigger(self::class, self::EVENT_DEFINE_LASTNAME_PREFIXES, $event);
        return $event->lastNamePrefixes;
    }

    public function getSalutations(): array
    {
        $event = new DefineNameSalutationsEvent([
            'salutations' => parent::getSalutations(),
        ]);

        Event::trigger(self::class, self::EVENT_DEFINE_SALUTATIONS, $event);
        return $event->salutations;
    }

    public function getSuffixes(): array
    {
        $event = new DefineNameSuffixesEvent([
            'suffixes' => parent::getSuffixes(),
        ]);

        Event::trigger(self::class, self::EVENT_DEFINE_SUFFIXES, $event);
        return $event->suffixes;
    }
}
