<?php

namespace Plugin\News\Entity;

/** Publication status for a NewsArticle, same lifecycle as Plugin\Page\Entity\PageStatus. */
enum NewsArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
