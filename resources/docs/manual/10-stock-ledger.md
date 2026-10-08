# Stock Ledger

The **Stock Ledger** shows the running balance behind a single supplier item. It
is read-only and available to every role, since staff need to see the balance of
the items they move.

---

## Opening the ledger

There are two ways to reach it:

1. On the **Transactions** screen, click an **item code** in the balance table.
2. On the **Dashboard**, click an item code in the recent-activity list.

The ledger opens at `/stock/{id}` for that supplier item.

---

## What the ledger shows

At the top, a summary of the supplier item:

| Field          | Example value                    |
| -------------- | -------------------------------- |
| Supplier       | Zuellig Pharma Corporation       |
| Item           | CONMED0367 — Alitraq, 76g sachet |
| Unit           | sach                             |
| Contract price | ₱1,250.00                        |
| Active         | Yes                              |

Below that, the ledger for the current period:

- **Beginning balance** — the quantity and cost carried in from the previous
  period.
- **Each transaction** in date order, with the running balance after it.
- **Ending balance** — the quantity and cost on hand now.

---

## Reading the ledger

Each transaction row shows the date, type, quantity, unit cost, total cost, ward
(where relevant), the profile who recorded it, and the **running quantity** and
**running cost** after that movement.

| Date       | Type                 | Qty | Running qty | Running cost |
| ---------- | -------------------- | --- | ----------- | ------------ |
| 2026-07-01 | Received             | 100 | 100         | ₱125,000.00  |
| 2026-07-05 | Consumption          | 20  | 80          | ₱100,000.00  |
| 2026-07-10 | Return from Ward     | 5   | 85          | ₱106,250.00  |
| 2026-07-12 | Return to Supplier   | 10  | 75          | ₱93,750.00   |
| 2026-07-15 | Transfer to Pharmacy | 15  | 60          | ₱75,000.00   |
| 2026-07-20 | Write-off            | 8   | 52          | ₱65,000.00   |

The ending balance is **52 sach** at **₱65,000.00**.

---

## Reversals in the ledger

A reversed transaction and its reversal both appear, and they net to zero. The
reversal is marked so you can tell it apart from an original movement.

---

## Why the ledger matters

The ledger is the audit-friendly view of an item: it shows exactly which
movements produced the current balance, in order. Use it to:

- Explain a balance to a supervisor.
- Trace a discrepancy back to the transaction that caused it.
- Confirm that a correction (reversal) has been applied.
