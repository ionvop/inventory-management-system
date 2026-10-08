# Reports

The **Reports** screen produces the department's monthly stock report in its
existing layout. Viewing and exporting reports is a **supervisor/administrator**
task.

Open it from **Reports** in the sidebar.

---

## Selecting a period

Use the **period selector** at the top to choose which month to report on. The
most recent period is shown by default. If no periods exist yet, the report is
empty.

---

## What the report contains

The report is grouped by **supplier**. Each supplier group lists its items, and
each item row shows:

| Column                   | Meaning                                     |
| ------------------------ | ------------------------------------------- |
| **Item code**            | The item's code, e.g. `CONMED0367`.         |
| **Item description**     | The item's description.                     |
| **Unit**                 | The unit of measure.                        |
| **Batch no.**            | The batch number(s) for the item.           |
| **Expiration date**      | The batch expiration date(s).               |
| **Contract price**       | The price in effect.                        |
| **Beginning**            | Opening quantity and cost for the period.   |
| **Received**             | Quantity and cost received.                 |
| **Return from Ward**     | Quantity and cost returned from wards.      |
| **Return to Supplier**   | Quantity and cost returned to the supplier. |
| **Transfer to Pharmacy** | Quantity and cost transferred out.          |
| **Consumption**          | Quantity and cost consumed.                 |
| **Write-off**            | Quantity and cost written off.              |
| **Ending**               | Closing quantity and cost for the period.   |

Each movement column has a **quantity** and a **total cost** sub-column. The
report also shows a **per-supplier subtotal** and a **grand total**.

---

## Remarks

Below the table, the **Inventory remarks** section lists notable events for the
period. Remarks are **derived from the data**, not typed in at report time:

- **Write-offs** — each write-off transaction, with its reason.
- **Expired batches** — batches that expired in the period.
- **Near-expiry batches** — batches approaching expiry.
- **Damaged batches** — batches flagged as damaged.

If there is nothing to report, the section shows _"No remarks for this period."_

---

## Frozen figures for closed periods

When a period is **closed**, the report reproduces the **frozen snapshot** taken
at close. This means regenerating the report for a closed month always produces
the same figures, even if later months change.

For an **open** period, the report is derived live from the transactions recorded
so far.

---

## Exporting to Excel

1. Select the period.
2. Click **Export to Excel**.

A file named `inventory-report-YYYY-MM.xlsx` downloads. It reproduces the
department's existing spreadsheet layout: the title band, the two-row header,
supplier bands, one row per batch, per-supplier subtotals, a grand total, and the
remarks block with the **Prepared by** attribution.

---

## Exporting to PDF

1. Select the period.
2. Click **Export to PDF**.

A file named `inventory-report-YYYY-MM.pdf` downloads. The PDF is generated from
the **same workbook** as the Excel export, so the two are identical by
construction.

---

## Prepared by / Received by

The report's signature block is filled from recorded data rather than left blank:

- **Prepared by** — the profile who closed the period.
- **Received by** — a signature label for the receiving party.

---

## Regenerating a report

Because closed periods are frozen, you can regenerate any past month's report on
demand and always get the same figures. Simply select the period and view or
export it again.
