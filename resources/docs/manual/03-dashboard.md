# Dashboard

The **Dashboard** is the landing screen after you select a profile. It is
action-oriented: it surfaces the figures that need attention, scoped to your
role, plus a few at-a-glance totals and the latest movements.

Open it any time from **Dashboard** in the sidebar.

---

## Alerts

The top of the dashboard shows **alerts** — counts of things that may need
action. Each alert links to the screen where you resolve it. Which alerts you
see depends on your role.

| Alert                       | Shown to                  | What it means                                                                      | Where it links |
| --------------------------- | ------------------------- | ---------------------------------------------------------------------------------- | -------------- |
| **Expired batches**         | All roles                 | Expired stock must be pulled out before it can be used.                            | Batches        |
| **Negative balances**       | All roles                 | A negative balance blocks period close and signals a data error to correct.        | Transactions   |
| **Damaged batches**         | All roles                 | Damaged stock is flagged for pull-out and appears in report remarks.               | Batches        |
| **Near-expiry batches**     | All roles                 | Pull out before the expiry date to avoid waste.                                    | Batches        |
| **Low-stock items**         | All roles                 | Running low may need a new receipt to avoid stock-outs.                            | Transactions   |
| **Periods ready to close**  | Supervisor, Administrator | A past month is still open; close it to freeze figures and carry balances forward. | Periods        |
| **Overrides to review**     | Administrator             | Negative-balance overrides are high-impact and should be reviewed.                 | Transactions   |
| **Missing contract prices** | Administrator             | Items without an active contract price cannot be transacted against.               | Supplier Items |

Alerts are colour-coded by severity: **red** (danger), **amber** (warning) and
**neutral** (informational).

> **Example:** If batch `BATCH-2026-001` (expiring `2026-12-31`) is within the
> near-expiry window, the **Near-expiry batches** alert shows a count of `1`.
> Clicking it opens the Batches screen filtered to that batch.

---

## At a glance

Below the alerts, the **At a glance** section shows the current period and a few
totals:

| Figure                       | Meaning                                                                         |
| ---------------------------- | ------------------------------------------------------------------------------- |
| **Period**                   | The current calendar month, e.g. _July 2026_, and whether it is open or closed. |
| **Active supplier items**    | How many supplier items currently have an active contract price.                |
| **Stock value**              | The total cost of stock on hand across all items.                               |
| **Transactions this period** | How many movements have been recorded in the current month.                     |
| **Near-expiry window**       | The number of days before expiry at which a batch is flagged (default 90).      |
| **Low-stock threshold**      | The quantity at or below which an item is considered low (default 10).          |

---

## Recent activity

The bottom of the dashboard lists the **last 10 transactions**, newest first.
Each row shows the movement type, supplier, item code, quantity, total cost,
date, the profile who recorded it, and the ward (where relevant).

Click an item code to open its **stock ledger** and see the running balance
behind it.

---

## Using the dashboard as a daily checklist

A practical routine at the start of each day:

1. Check **Expired** and **Near-expiry batches** — pull out anything that has
   lapsed or is about to.
2. Check **Damaged batches** — confirm damaged stock has been written off.
3. Check **Low-stock items** — flag anything that needs reordering.
4. Check **Negative balances** — correct any data errors before month-end.
5. Supervisors and administrators: check **Periods ready to close**.
