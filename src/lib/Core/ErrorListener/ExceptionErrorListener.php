<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\Core\ErrorListener;

use Antlr\Antlr4\Runtime\Error\Exceptions\RecognitionException;
use Antlr\Antlr4\Runtime\Error\Listeners\BaseErrorListener;
use Antlr\Antlr4\Runtime\Recognizer;
use EzSystems\EzPlatformQueryLanguage\Core\Exception\SyntaxErrorException;

final class ExceptionErrorListener extends BaseErrorListener
{
    public function syntaxError(Recognizer $recognizer, ?object $offendingSymbol, int $line, int $charPositionInLine, string $msg, ?RecognitionException $e): void
    {
        throw new SyntaxErrorException(sprintf('Line %d:%d %s', $line, $charPositionInLine, $msg), 0, $e);
    }
}
