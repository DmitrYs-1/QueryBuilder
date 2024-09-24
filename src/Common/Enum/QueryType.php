<?php

namespace DmitrYs\QueryBuilder\Common\Enum;

enum QueryType
{
    case Select;
    case Insert;
    case Update;
    case Delete;
}
