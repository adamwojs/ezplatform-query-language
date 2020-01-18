<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\API\Repository;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;

interface QueryLanguageService
{
    public function compile(string $query, array $params = []): Query;

    public function execute(
        string $query,
        array $params = [],
        array $languageFilter = [],
        bool $filterOnUserPermissions = true
    ): SearchResult;
}
