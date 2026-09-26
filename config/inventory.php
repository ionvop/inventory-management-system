<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Near-expiry threshold
    |--------------------------------------------------------------------------
    |
    | The number of days before a batch's expiration date at which it is
    | automatically flagged as "near expiry" (FR-3.2). The default of 90 days
    | matches the department's pull-out lead time.
    |
    */

    'near_expiry_days' => (int) env('INVENTORY_NEAR_EXPIRY_DAYS', 90),

];
