<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\Core\QueryType;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\Core\QueryType\OptionsResolverBasedQueryType;
use EzSystems\EzPlatformQueryLanguage\API\Repository\QueryLanguageService;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EZQLQueryType extends OptionsResolverBasedQueryType
{
    /** @var \EzSystems\EzPlatformQueryLanguage\API\Repository\QueryLanguageService */
    private $queryLanguageService;

    public function __construct(QueryLanguageService $queryLanguageService)
    {
        $this->queryLanguageService = $queryLanguageService;
    }

    protected function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('query');
        $optionsResolver->setDefaults([
            'bind' => [],
        ]);
    }

    protected function doGetQuery(array $parameters): Query
    {
        return $this->queryLanguageService->compile(
            $parameters['query'],
            $parameters['bind']
        );
    }

    public static function getName(): string
    {
        return 'EZQL';
    }
}
