<?php

namespace App\Entity;

/**
 * The three kinds of upload the generic File entity can represent, each
 * mapped to its own storage subtree under upload/ (see File::TYPE_* paths
 * in FileUploadService).
 */
enum FileType: string
{
    case Image = 'img';
    case Pdf = 'pdf';
    case Zip = 'zip';
}
