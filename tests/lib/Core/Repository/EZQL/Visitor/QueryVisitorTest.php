<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\Tests\Core\Repository\EZQL\Visitor;

use Antlr\Antlr4\Runtime\CommonTokenStream;
use Antlr\Antlr4\Runtime\InputStream;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\DateMetadata;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Field;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\FullText;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Location\IsMainLocation;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LocationId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalNot;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalOr;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Visibility;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\ContentName;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\Location\Priority;
use EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL\ErrorListener\ExceptionErrorListener;
use EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL\Exception\MissingParameterException;
use EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL\Exception\SyntaxErrorException;
use EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL\Parser\EZQLLexer;
use EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL\Parser\EZQLParser;
use EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL\Visitor\QueryVisitor;
use EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL\Visitor\QueryVisitorResult;
use PHPUnit\Framework\TestCase;

final class QueryVisitorTest extends TestCase
{
    /**
     * @dataProvider dataProviderForSelectLocation
     */
    public function testSelectLocation(string $query, array $params, QueryVisitorResult $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->doVisitQuery($query, $params));
    }

    public function dataProviderForSelectLocation(): iterable
    {
        yield 'select with offset' => [
            'SELECT LOCATION OFFSET 100',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'offset' => 100,
                ])
            ),
        ];

        yield 'select with limit' => [
            'SELECT LOCATION LIMIT 100',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'limit' => 100,
                ])
            ),
        ];

        yield 'operator: equals' => [
            'SELECT LOCATION FILTER BY ContentTypeIdentifier = "article"',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new ContentTypeIdentifier('article'),
                ])
            ),
        ];

        yield 'operator: not equals' => [
            'SELECT LOCATION FILTER BY LocationId != 1',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new LogicalNot(new LocationId(1)),
                ])
            ),
        ];

        yield 'operator: in' => [
            'SELECT LOCATION FILTER BY LocationId IN (1, 2, 3)',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new LocationId([1, 2, 3]),
                ])
            ),
        ];

        yield 'operator: not in' => [
            'SELECT LOCATION FILTER BY LocationId NOT IN (1, 2, 3)',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new LogicalNot(new LocationId([1, 2, 3])),
                ])
            ),
        ];

        yield 'is main location' => [
            'SELECT LOCATION FILTER BY IS MAIN LOCATION',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new IsMainLocation(IsMainLocation::MAIN),
                ])
            ),
        ];

        yield 'is not main location' => [
            'SELECT LOCATION FILTER BY IS NOT MAIN LOCATION',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new IsMainLocation(IsMainLocation::NOT_MAIN),
                ])
            ),
        ];

        yield 'is visible' => [
            'SELECT LOCATION FILTER BY IS VISIBLE',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new Visibility(Visibility::VISIBLE),
                ])
            ),
        ];

        yield 'is hidden' => [
            'SELECT LOCATION FILTER BY IS HIDDEN',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new Visibility(Visibility::HIDDEN),
                ])
            ),
        ];

        yield 'match all' => [
            'SELECT LOCATION FILTER BY MATCH ALL',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new Query\Criterion\MatchAll(),
                ])
            ),
        ];

        yield 'match none' => [
            'SELECT LOCATION FILTER BY MATCH NONE',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new Query\Criterion\MatchNone(),
                ])
            ),
        ];

        yield 'field is eq' => [
            'SELECT LOCATION FILTER BY FIELD title = :title',
            [
                'title' => 'Lorem ipsum dolor',
            ],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new Field('title', Operator::EQ, 'Lorem ipsum dolor'),
                ])
            ),
        ];

        yield 'field is not eq' => [
            'SELECT LOCATION FILTER BY FIELD title != :title',
            [
                'title' => 'Lorem ipsum dolor',
            ],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new LogicalNot(new Field('title', Operator::EQ, 'Lorem ipsum dolor')),
                ])
            ),
        ];

        yield 'created' => [
            'SELECT LOCATION FILTER BY CREATED >= :created',
            [
                'created' => 1579637460,
            ],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new DateMetadata(DateMetadata::CREATED, Operator::GTE, 1579637460),
                ])
            ),
        ];

        yield 'modified' => [
            'SELECT LOCATION FILTER BY MODIFIED >= :modified',
            [
                'modified' => 1579637460,
            ],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new DateMetadata(DateMetadata::MODIFIED, Operator::GTE, 1579637460),
                ])
            ),
        ];

        yield 'operator: and' => [
            'SELECT LOCATION FILTER BY LocationId = 1 AND ParentLocationId = 2',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new LocationId(1),
                        new ParentLocationId(2),
                    ]),
                ])
            ),
        ];

        yield 'operator: or' => [
            'SELECT LOCATION FILTER BY LocationId = 1 OR ParentLocationId = 2',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new LogicalOr([
                        new LocationId(1),
                        new ParentLocationId(2),
                    ]),
                ])
            ),
        ];

        yield 'expression group' => [
            'SELECT LOCATION FILTER BY (LocationId = 1 AND ParentLocationId = 2) OR LocationId = 3',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'filter' => new LogicalOr([
                        new LogicalAnd([
                            new LocationId(1),
                            new ParentLocationId(2),
                        ]),
                        new LocationId(3),
                    ]),
                ])
            ),
        ];

        yield 'sort' => [
            'SELECT LOCATION ORDER BY ContentName DESC, Location\Priority ASC',
            [],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'sortClauses' => [
                        new ContentName(Query::SORT_DESC),
                        new Priority(Query::SORT_ASC),
                    ],
                ])
            ),
        ];

        yield 'parameters binding' => [
            'SELECT LOCATION 
                FILTER BY LocationId = :locationId 
                QUERY FullText = :query 
                OFFSET :offset 
                LIMIT :limit',
            [
                'locationId' => 1,
                'query' => 'Hello World',
                'limit' => 25,
                'offset' => 100,
            ],
            new QueryVisitorResult(
                QueryVisitorResult::TARGET_LOCATION,
                new LocationQuery([
                    'query' => new FullText('Hello World'),
                    'filter' => new LocationId(1),
                    'limit' => 25,
                    'offset' => 100,
                ])
            ),
        ];
    }

    public function testVisitorThrowsMissingParameterException(): void
    {
        $this->expectException(MissingParameterException::class);
        $this->expectErrorMessage('Missing parameter: query');

        $this->doVisitQuery(
            'SELECT LOCATION QUERY FullText = :query',
            [
                /* No parameters */
            ]
        );
    }

    public function testParseError(): void
    {
        $this->expectException(SyntaxErrorException::class);
        $this->expectErrorMessage("Line 1:21 mismatched input 'FullText' expecting {K_FALSE, K_TRUE, INT, DOUBLE, STRING, PARAMETER_NAME}");

        $this->doVisitQuery('SELECT CONTENT LIMIT FullText = "foo"');
    }

    private function doVisitQuery(string $query, array $params = []): QueryVisitorResult
    {
        $lexer = new EZQLLexer(InputStream::fromString($query));

        $parser = new EZQLParser(new CommonTokenStream($lexer));
        $parser->removeErrorListeners();
        $parser->addErrorListener(new ExceptionErrorListener());

        return $parser->stmt()->accept(
            new QueryVisitor($params)
        );
    }
}
