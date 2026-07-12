<?php

namespace App\Models\Concerns;

use DateTimeInterface;


trait SerializesDatesReadably
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('H:i:s') === '00:00:00'
            ? $date->format('d M Y')          // 05 Aug 2026
            : $date->format('d M Y, H:i');    // 05 Aug 2026, 14:30
    }
}
