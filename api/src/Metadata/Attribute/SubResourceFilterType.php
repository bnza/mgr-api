<?php

namespace App\Metadata\Attribute;

enum SubResourceFilterType: string
{
    case EXISTS = 'exists';
    case SEARCH = 'search';
    case RANGE = 'range';

    case UNACCENTED_SEARCH = 'unaccented_search';
}
