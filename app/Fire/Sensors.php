<?php

namespace App\Fire;

class Sensors
{
    public const MODIS = 'modis';

    public const VIIRS = 'viirs';

    /** First day of the MODIS (Terra) and VIIRS (Suomi NPP) fire records in FIRMS. */
    public const MODIS_START = '2000-11-01';

    public const VIIRS_START = '2012-01-20';
}
