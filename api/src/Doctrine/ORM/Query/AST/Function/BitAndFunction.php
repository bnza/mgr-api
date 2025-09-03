<?php

declare(strict_types=1);

namespace App\Doctrine\ORM\Query\AST\Function;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Custom DQL function for SQL bitwise AND operator.
 *
 * Usage in DQL:
 *   BIT_AND(e.endsMask, :mask)
 *
 * Produces SQL:
 *   (e.ends_mask & :mask)
 */
class BitAndFunction extends FunctionNode
{
    private ?Node $leftExpression = null;
    private ?Node $rightExpression = null;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return '('
            .$this->leftExpression->dispatch($sqlWalker)
            .' & '
            .$this->rightExpression->dispatch($sqlWalker)
            .')';
    }

    public function parse(Parser $parser): void
    {
        // Match function name
        $parser->match(TokenType::T_IDENTIFIER);

        // Open parenthesis
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        // First argument (identifier/expression)
        // Use ArithmeticPrimary to accept identifiers, numeric literals, parameters, etc.
        $this->leftExpression = $parser->ArithmeticPrimary();

        // Comma
        $parser->match(TokenType::T_COMMA);

        // Second argument (parameter/expression)
        $this->rightExpression = $parser->ArithmeticPrimary();

        // Close parenthesis
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
