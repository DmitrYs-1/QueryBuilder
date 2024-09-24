<?php

namespace DmitrYs\QueryBuilder\Common\Enum;

enum WhereType: string
{
    case AND = 'AND';
    case OR = 'OR';
}