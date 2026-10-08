# Periods

A **Period** is a calendar month that groups transactions. Closing a period
freezes its figures and carries each item's ending balance forward as the next
month's beginning balance. Closing is a **supervisor/administrator** task;
reopening is **administrator** only.

Open it from **Periods** in the sidebar.

---

## How periods are created

You never create a period by hand. A period is created **automatically** the
first time a transaction is recorded for a month. For example, recording a
transaction dated `2026-07-01` creates the **July 2026** period.

---

## The period list

The screen lists every period, newest first, with its status and a few counts.

| Period    | Status | Transactions | Negative items | Closed by  |
| --------- | ------ | ------------ | -------------- | ---------- |
| July 2026 | Open   | 6            | 0              | —          |
| June 2026 | Closed | 12           | 0              | Jose Rizal |

---

## Closing a period

Closing a month is the department's month-end sign-off. It:

1. **Freezes** all transactions dated within that month against further edits.
2. **Snapshots** each supplier item's ending quantity and cost.
3. **Carries** that ending balance forward as the next month's beginning balance.

To close a period:

1. Find the period's row (for example, **July 2026**).
2. Click **Close**.
3. Confirm.

The status becomes **Closed**, and the closing profile and timestamp are
recorded.

> **Before closing:** make sure every movement for the month has been recorded.
> Once closed, the month cannot accept new transactions.

---

## Why a period cannot be closed

A period is **blocked from closing** while any item shows a **negative balance**.
The period list shows a **Negative items** count so you can see the problem
before you try.

To resolve it:

1. Open **Transactions** and find the item with the negative balance (the
   dashboard's **Negative balances** alert links here).
2. Correct the data — usually a missing receipt or a mis-keyed quantity.
3. Once every balance is zero or positive, close the period.

---

## Reopening a period (administrators)

If a closed month needs a correction, an administrator can reopen it.

1. Find the closed period's row.
2. Click **Reopen**.
3. Enter a **reason** for reopening, e.g. `Missing receipt for July`.
4. Confirm.

The period returns to **Open** and can accept transactions again. The reason is
recorded in the audit log.

> **Caution:** Reopening changes figures that may already have been reported.
> Always give a clear reason.

---

## The month-end routine

1. Record every remaining movement for the month.
2. Check the dashboard for **Negative balances** and correct them.
3. Close the period.
4. Generate and export the report (see _Reports_).
