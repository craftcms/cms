<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\RedirectNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class RedirectTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): RedirectNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'path' => $this->parser->parseExpression(),
        ];

        if ($stream->test(Token::NUMBER_TYPE)) {
            $nodes['httpStatusCode'] = $this->parser->parseExpression();
        } else {
            $nodes['httpStatusCode'] = new ConstantExpression(302, 1);
        }

        // Parse flash message(s)
        while ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $type = $stream->expect(Token::NAME_TYPE, ['notice', 'error'])->getValue();
            $nodes[$type] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RedirectNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'redirect';
    }
}
