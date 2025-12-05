<?php

namespace App\Metadata\Attribute\SubResourceFilters;

enum ApiSubResourceFilterType: string
{
    case BOOLEAN = 'boolean';
    case EXISTS = 'exists';
    case SEARCH = 'search';
    case RANGE = 'range';
    case UNACCENTED_SEARCH = 'unaccented_search';
}
