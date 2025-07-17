<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Function;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Custom DQL function for PostgreSQL unaccent_immutable(text) function.
 *
 * Usage in DQL: SELECT unaccent_immutable(e.name) FROM Entity e
 */
class UnaccentImmutableFunction extends FunctionNode
{
    private ?Node $stringExpression = null;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'unaccent_immutable('.$this->stringExpression->dispatch($sqlWalker).')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->stringExpression = $parser->StringExpression();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
