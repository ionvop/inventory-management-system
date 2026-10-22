# Bid Quantities

A **bid quantity** is the amount the department has committed to buy from a
supplier for an item — for example, 10,000 cans. It is usually set once every
six months. As stock is received against that supplier item, the bid is
deducted, so the **remaining** quantity shows how much of the commitment is
still outstanding. When the remaining quantity drops to the reorder threshold,
it is time to place a new bid.

Setting a bid is a **supervisor/administrator** task.

Open it from **Bids** in the sidebar.

> **Note:** Bid quantities are for internal tracking only. They are **never**
> included in the exported Excel or PDF reports.

---

## Why bids are tracked separately

A supplier item's **contract price** can change over time, and each price change
is stored as a new record so history is preserved. A bid, however, belongs to
the **supplier and item pair**, not to a single price record — so it survives a
price change. The system therefore tracks bids against the pair, and the current
bid is always the most recently set one.

---

## The bid list

The screen lists every transactable supplier item (one with an active contract
price) with its current bid, how much has been received since the bid was set,
and the remaining quantity.

| Supplier                   | Item                             | Bid quantity | Set date   | Received since | Remaining | Status  |
| -------------------------- | -------------------------------- | ------------ | ---------- | -------------- | --------- | ------- |
| Zuellig Pharma Corporation | CONMED0367 — Alitraq, 76g sachet | 10,000.00    | 2026-01-01 | 9,200.00       | 800.00    | Reorder |

A pair that has never been bid on shows **No bid set**, so you can set one.

---

## Setting a bid quantity

1. Click **Set bid** (or **Set new bid** to replace the current one).
2. Fill in the form:

    | Field             | Example value                             |
    | ----------------- | ----------------------------------------- |
    | **Supplier item** | `Zuellig Pharma Corporation — CONMED0367` |
    | **Bid quantity**  | `10000`                                   |
    | **Set date**      | `2026-01-01`                              |

3. Click **Set bid quantity**. A toast confirms _"Bid quantity set."_

Setting a bid creates a **new record** rather than editing the old one, so the
history of bids is preserved.

---

## How the remaining quantity is calculated

The remaining quantity is:

```
Remaining = Bid quantity − Received since the set date
```

- Only **received** movements count against a bid. Consumption, transfers,
  returns and write-offs do not.
- Only receipts dated **on or after the bid's set date** count, so a bid set
  today is not reduced by stock that arrived before it.
- Reversing a receipt adds the quantity back to the bid.

The remaining quantity can go **negative** if more stock arrives than was bid.
That is a signal the bid was under-estimated, not an error.

---

## When to reorder

When the remaining quantity drops to the **reorder threshold** or below, the row
is flagged **Reorder** and the dashboard shows a **Bids to reorder** alert. The
threshold is a configurable quantity (default 1,000 units) that the department
can tune without a code change.

---

## Bids and the reports

Bid quantities are deliberately **excluded** from the monthly report and its
Excel/PDF exports. They are an internal planning aid, not part of the
department's official stock ledger.
