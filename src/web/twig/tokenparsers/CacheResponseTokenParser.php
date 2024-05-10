<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\CacheResponseNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class CacheResponse
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.10.0
 */
class CacheResponseTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): CacheResponseNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [];

        $attributes = [
            'durationNum' => null,
            'durationUnit' => null,
        ];

        if ($stream->test(Token::NAME_TYPE, 'never')) {
            $stream->next();
            $attributes['durationNum'] = 0;
        } elseif ($stream->test(Token::NAME_TYPE, 'for')) {
            $stream->next();
            $attributes['durationNum'] = $stream->expect(Token::NUMBER_TYPE)->getValue();
            $attributes['durationUnit'] = $stream->expect(Token::NAME_TYPE,
                [
                    'sec',
                    'secs',
                    'second',
                    'seconds',
                    'min',
                    'mins',
                    'minute',
                    'minutes',
                    'hour',
                    'hours',
                    'day',
                    'days',
                    'fortnight',
                    'fortnights',
                    'forthnight',
                    'forthnights',
                    'month',
                    'months',
                    'year',
                    'years',
                    'week',
                    'weeks',
                ])->getValue();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new CacheResponseNode($nodes, $attributes, $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'cacheResponse';
    }
}
