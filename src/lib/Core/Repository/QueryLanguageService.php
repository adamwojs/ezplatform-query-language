<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\Core\Repository;

use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\InputStream;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use EzSystems\EzPlatformQueryLanguage\API\Repository\QueryLanguageService as QueryLanguageServiceInterface;
use EzSystems\EzPlatformQueryLanguage\Core\Parser\EZQLLexer;
use EzSystems\EzPlatformQueryLanguage\Core\Parser\EZQLParser;
use EzSystems\EzPlatformQueryLanguage\Core\Visitor\QueryVisitor;
use EzSystems\EzPlatformQueryLanguage\Core\Visitor\QueryVisitorResult;

final class QueryLanguageService implements QueryLanguageServiceInterface
{
    /** @var \eZ\Publish\API\Repository\SearchService */
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function compile(string $query, array $params = []): Query
    {
        return $this->compileQuery($query, $params)->getQuery();
    }

    public function execute(
        string $query,
        array $params = [],
        array $languageFilter = [],
        bool $filterOnUserPermissions = true
    ): SearchResult {
        $result = $this->compileQuery($query, $params);

        $args = [$result->getQuery(), $languageFilter, $filterOnUserPermissions];
        switch ($result->getTarget()) {
            case QueryVisitorResult::TARGET_CONTENT:
                return $this->searchService->findContent(...$args);
            case QueryVisitorResult::TARGET_CONTENT_INFO:
                return $this->searchService->findContentInfo(...$args);
            case QueryVisitorResult::TARGET_LOCATION:
                return $this->searchService->findLocations(...$args);
        }
    }

    private function compileQuery(string $query, array $params): QueryVisitorResult
    {
        $lexer = new EZQLLexer(InputStream::fromString($query));
        $parser = new EZQLParser(new CommonTokenStream($lexer));

        return $parser->select()->accept(new QueryVisitor($params));
    }
}
