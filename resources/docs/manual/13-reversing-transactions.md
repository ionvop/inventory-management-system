# Reversing Transactions

Transactions are **immutable** — once saved, they cannot be edited or deleted.
This preserves an audit trail and keeps historical figures trustworthy. When a
transaction is wrong, you correct it by **reversing** it. Reversing is an
**administrator** task.

---

## What a reversal does

A reversal posts a **new transaction** that references the original. It carries
the same type, quantity, unit cost, supplier item, batch and ward as the
original, so the two **net to zero** in the balance.

Key properties:

- The reversal is dated **today** and lands in the **current open period**, so a
  closed period stays frozen.
- A **remark is required** — it explains why the correction was made.
- The original transaction is marked as **reversed**.
- Both the reversal and the original are recorded in the audit log.

---

## When to reverse

Use a reversal when a transaction was recorded incorrectly, for example:

- The wrong item was selected.
- The quantity was mis-keyed.
- A duplicate entry was recorded.
- A movement was recorded against the wrong ward.

---

## How to reverse a transaction

1. Open **Transactions**.
2. Find the transaction in the recent-activity list.
3. Click **Reverse** on its row.
4. Enter a **remark** explaining the correction, e.g.
   `Recorded against the wrong item.`
5. Confirm.

The reversal appears in the ledger alongside the original, and the balance
returns to what it was before the original was recorded.

---

## Rules and guards

The system prevents reversals that would corrupt the ledger:

| Guard                                                   | Why                                              |
| ------------------------------------------------------- | ------------------------------------------------ |
| **A reversal cannot itself be reversed.**               | Keeps the ledger a clean original/reversal pair. |
| **A transaction can only be reversed once.**            | Prevents double-cancelling.                      |
| **The current period must be open.**                    | A closed period is frozen.                       |
| **The reversal must not drive the balance below zero.** | Unless an administrator overrides with a reason. |

---

## Example

Suppose Maria records a **Consumption** of `20` against the wrong item. Ana, an
administrator, reverses it:

| Field           | Value                              |
| --------------- | ---------------------------------- |
| Original        | Consumption, 20, dated 2026-07-05  |
| Reversal remark | `Recorded against the wrong item.` |
| Reversal date   | today (current open period)        |

The original and reversal net to zero, so the balance is restored. Maria then
records the consumption against the correct item.

---

## Reversals in reports and the ledger

- In the **stock ledger**, the reversal is marked and nets against the original.
- In the **report**, a reversal flips the sign of its movement column, so the
  original and reversal cancel out.
- In the **audit log**, both rows are recorded with the action **Reverse**.
