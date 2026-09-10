<?php

namespace App\Entity;

/** Open Graph object type (step 37), one of the four values requested in the spec. */
enum OgType: string
{
    case Website = 'website';
    case Article = 'article';
    case Product = 'product';
    case Profile = 'profile';
}
