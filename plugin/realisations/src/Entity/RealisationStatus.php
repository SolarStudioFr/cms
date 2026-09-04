<?php

namespace Plugin\Realisations\Entity;

/** Publication status for a Realisation, same lifecycle as Plugin\Page\Entity\PageStatus. */
enum RealisationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
