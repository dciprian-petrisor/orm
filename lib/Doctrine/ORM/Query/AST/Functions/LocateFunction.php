<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "LOCATE" "(" StringPrimary "," StringPrimary ["," SimpleArithmeticExpression]")"
 *
 * @link    www.doctrine-project.org
 */
class LocateFunction extends FunctionNode
{
    public Node $firstStringPrimary;
    public Node $secondStringPrimary;

    /** @var SimpleArithmeticExpression|bool */
    public $simpleArithmeticExpression = false;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        $firstString  = $sqlWalker->walkStringPrimary($this->firstStringPrimary);
        $secondString = $sqlWalker->walkStringPrimary($this->secondStringPrimary);

        if ($this->simpleArithmeticExpression) {
            return $platform->getLocateExpression(
                $secondString,
                $firstString,
                $sqlWalker->walkSimpleArithmeticExpression($this->simpleArithmeticExpression),
            );
        }

        return $platform->getLocateExpression($secondString, $firstString);
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->firstStringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->secondStringPrimary = $parser->StringPrimary();

        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->simpleArithmeticExpression = $parser->SimpleArithmeticExpression();
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
