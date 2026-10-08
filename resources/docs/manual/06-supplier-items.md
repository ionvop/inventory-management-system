# Supplier Items (Contract Pricing)

A **Supplier Item** links a supplier and an item at a specific **contract
price**, with an **effective date**. This is the record that transactions are
actually recorded against. Managing supplier items is an **administrator** task.

Open it from **Supplier Items** in the sidebar.

---

## Why supplier items exist

The same item can be bought from different suppliers at different prices, and a
supplier's price can change over time. Rather than overwriting a price, the
system keeps **one row per price**, each with an effective date. This means a
past transaction always retains the price that was in effect when it happened.

---

## The supplier item list

The screen lists every supplier item with its supplier, item, price, effective
date and active flag.

| Supplier                   | Item                             | Price     | Effective date | Active |
| -------------------------- | -------------------------------- | --------- | -------------- | ------ |
| Zuellig Pharma Corporation | CONMED0367 — Alitraq, 76g sachet | ₱1,250.00 | 2026-01-01     | Yes    |

---

## Adding a supplier item

1. Click **Add supplier item**.
2. Fill in the form:

    | Field              | Example value                      |
    | ------------------ | ---------------------------------- |
    | **Supplier**       | `Zuellig Pharma Corporation`       |
    | **Item**           | `CONMED0367 — Alitraq, 76g sachet` |
    | **Price**          | `1250.00`                          |
    | **Effective date** | `2026-01-01`                       |
    | **Active**         | checked                            |

3. Click **Save**. A toast confirms _"Supplier item created."_

---

## Changing a contract price

A price change is **not** an edit — it is a **new row**. This preserves history.

1. Click **Add supplier item**.
2. Choose the same supplier and item, enter the **new price** and the date it
   takes effect:

    | Field              | Example value                      |
    | ------------------ | ---------------------------------- |
    | **Supplier**       | `Zuellig Pharma Corporation`       |
    | **Item**           | `CONMED0367 — Alitraq, 76g sachet` |
    | **Price**          | `1350.00`                          |
    | **Effective date** | `2026-08-01`                       |

3. Click **Save**.

From `2026-08-01` onward, new transactions use ₱1,350.00. Transactions recorded
before that date keep ₱1,250.00.

> **Note:** The **price** field on an existing supplier item cannot be edited.
> Only the **effective date** and the **active** flag can be changed. To change a
> price, create a new row.

---

## Which price is used?

When you record a transaction, the system uses the **active** supplier item whose
effective date is the greatest date that is **on or before today**. If no such
row exists, the supplier item cannot be transacted against (see below).

---

## Deactivating a supplier item

1. Click the **pencil** icon on the row.
2. Clear the **Active** checkbox.
3. Click **Save**.

A deactivated supplier item no longer appears in the transaction form.

---

## Deleting a supplier item

1. Click the **trash** icon on the row.
2. Confirm the deletion.

Deletion is **blocked** when the supplier item already has transactions, because
those transactions must keep their price history. Deactivate it instead.

---

## Missing contract prices

If an item has no active contract price, it cannot be transacted against. The
dashboard shows a **Missing contract prices** alert to administrators, linking
here so the gap can be filled.
