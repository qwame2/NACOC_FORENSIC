<?php

namespace App\Models;

/**
 * Backward compatibility alias for SuspectPreliminaryReport.
 * WHY: Legacy code and tests referencing SuspectNlrReport will resolve to suspect_preliminary_reports.
 */
class SuspectNlrReport extends SuspectPreliminaryReport
{
    protected $table = 'suspect_preliminary_reports';
}
