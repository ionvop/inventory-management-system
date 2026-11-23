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

    /*
    |--------------------------------------------------------------------------
    | Dashboard consumption window
    |--------------------------------------------------------------------------
    |
    | The rolling number of days the dashboard uses to summarise how much of
    | each supplier item has been consumed. The default of 30 days gives the
    | department a recent-usage view without changing the monthly report.
    |
    */

    'consumption_window_days' => (int) env('INVENTORY_CONSUMPTION_WINDOW_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Low-stock threshold
    |--------------------------------------------------------------------------
    |
    | The running quantity at or below which an active supplier item is
    | surfaced on the dashboard as low stock, so a new receipt can be arranged
    | before the item runs out (FR-5.2). The default of 10 units is a starting
    | point the department can tune without a code change.
    |
    */

    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Bid reorder threshold
    |--------------------------------------------------------------------------
    |
    | The remaining bid quantity at or below which a supplier item is flagged
    | as needing a new bid. A bid is the quantity committed to a supplier for
    | an item (typically set every six months); as stock is received the
    | remaining quantity falls, and this threshold is the point at which the
    | department knows it is time to order again. The default of 1,000 units is
    | a starting point the department can tune without a code change.
    |
    */

    'bid_reorder_threshold' => (int) env('INVENTORY_BID_REORDER_THRESHOLD', 1000),

];
