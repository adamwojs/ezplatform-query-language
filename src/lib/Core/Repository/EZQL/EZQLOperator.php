<?php

declare(strict_types=1);

namespace EzSystems\EzPlatformQueryLanguage\Core\Repository\EZQL;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator as BaseOperator;

final class EZQLOperator extends BaseOperator
{
    public const NEQ = '!=';
    public const NOT_IN = 'NOT IN';
}
