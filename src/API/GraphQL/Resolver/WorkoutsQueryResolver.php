<?php

namespace App\API\GraphQL\Resolver;

use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Resolver\ResolverMap;

class WorkoutsQueryResolver extends ResolverMap
{

    protected function map()
    {
        return [
            'Query' => [
                self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, \ArrayObject $context, ResolveInfo $info) {
                    if('id' === $info->fieldName) {
                        return 'id';
                    }
                }
            ]
        ];
    }
}