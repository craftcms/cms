<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Twig\Nodes\ExpiresNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class ExpiresTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): ExpiresNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];

        $attributes = [
            'durationNum' => 0,
            'durationUnit' => 'seconds',
        ];

        if ($stream->test(Token::OPERATOR_TYPE, 'in')) {
            $stream->next();
            $attributes['durationNum'] = $stream->expect(Token::NUMBER_TYPE)->getValue();
            $attributes['durationUnit'] = $stream->expect(Token::NAME_TYPE, DateTimeHelper::RELATIVE_TIME_UNITS)->getValue();
        } elseif ($stream->test(Token::NAME_TYPE, 'on')) {
            $stream->next();
            $nodes['expiration'] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new ExpiresNode($nodes, $attributes, $lineno);
    }

    public function getTag(): string
    {
        return 'expires';
    }
}
