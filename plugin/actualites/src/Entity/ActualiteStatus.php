<?php

namespace Plugin\Actualites\Entity;

/** Publication status for an Actualite, same lifecycle as Plugin\Page\Entity\PageStatus. */
enum ActualiteStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
