<?php

namespace EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;

/**
 * @internal
 */
interface EZQLProxyInterface
{
    public function execute(string $query, array $params = [], array $languageFilter = [], bool $filterOnUserPermissions = true): SearchResult;

    public function getQuery(string $query, array $params = []): Query;
}
