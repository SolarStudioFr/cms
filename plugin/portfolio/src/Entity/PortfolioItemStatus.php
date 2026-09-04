<?php

namespace Plugin\Portfolio\Entity;

/** Publication status for a PortfolioItem, same lifecycle as Plugin\Page\Entity\PageStatus. */
enum PortfolioItemStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
