<?php

namespace Plugin\Newsletter\Entity;

/**
 * Lifecycle of a newsletter campaign: Draft (still editable, nothing sent
 * yet), Sending (bulk send in progress or interrupted mid-way - step 24's
 * send-next endpoint resumes it safely), Sent (every subscriber snapshotted
 * at send-start has been mailed).
 */
enum CampaignStatus: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case Sent = 'sent';
}
