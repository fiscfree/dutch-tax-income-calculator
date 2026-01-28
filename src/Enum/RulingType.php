<?php

declare(strict_types=1);

namespace DutchTaxCalculator\Enum;

/**
 * Types of 30% ruling for expat tax benefits.
 *
 * @see https://www.belastingdienst.nl/wps/wcm/connect/bldcontentnl/belastingdienst/prive/internationaal/werken_wonen/tijdelijk_in_een_ander_land_werken/u_komt_in_nederland_werken/30_procent_regeling/voorwaarden_30_procent_regeling/u-hebt-een-specifieke-deskundigheid
 */
enum RulingType: string
{
    /**
     * Standard 30% ruling for workers with specific expertise.
     */
    case Normal = 'normal';

    /**
     * 30% ruling for young workers under 30 with a master's degree.
     */
    case YoungMaster = 'young';

    /**
     * 30% ruling for scientific researchers (no minimum income threshold).
     */
    case Research = 'research';
}
