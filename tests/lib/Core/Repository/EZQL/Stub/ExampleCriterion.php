<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\Tests\Core\Repository\EZQL\Stub;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

final class ExampleCriterion extends Criterion
{
    /** @var array */
    private $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }

    public function getSpecifications(): array
    {
        return [];
    }
}
