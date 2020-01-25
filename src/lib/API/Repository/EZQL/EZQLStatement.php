<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\API\Repository\EZQL;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;

interface EZQLStatement
{
    public function getParams(): array;

    public function setParams(array $params): void;

    public function bindParam(string $name, $value): void;

    public function setLanguageFilter(array $languageFilter): void;

    public function getLanguageFilter(): array;

    public function getFilterOnPermissions(): bool;

    public function setFilterOnPermissions(bool $filterOnPermissions): void;

    public function getEZQLQuery(): string;

    public function getQuery(array $params = null): Query;

    public function execute(array $params = null): SearchResult;
}
