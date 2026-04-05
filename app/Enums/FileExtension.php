<?php

declare(strict_types=1);

namespace App\Enums;

enum FileExtension: string
{
    case Xlsx = 'xlsx';
    case Csv = 'csv';
    case Pdf = 'pdf';
    case Jpg = 'jpg';
    case Png = 'png';
    case Json = 'json';
}
