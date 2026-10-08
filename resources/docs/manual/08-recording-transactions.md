# Recording Transactions

Every stock movement is recorded on the **Transactions** screen. Any role can
record transactions. Open it from **Transactions** in the sidebar.

The screen has two parts: the **entry form** at the top and a **balance table**
of active supplier items below it.

---

## The entry form

The form adapts to the movement type you choose. The fields are:

| Field                | Notes                                                                 |
| -------------------- | --------------------------------------------------------------------- |
| **Movement type**    | One of the six types. Defaults to _Received_.                         |
| **Supplier item**    | The supplier + item to move. Only active supplier items appear.       |
| **Quantity**         | A positive number, e.g. `50`.                                         |
| **Transaction date** | Defaults to today. Determines which period the movement lands in.     |
| **Ward**             | Shown for _Return from Ward_ (required) and _Consumption_ (optional). |
| **Batch number**     | Shown for _Received_ only.                                            |
| **Expiration date**  | Shown for _Received_ only.                                            |
| **Remark**           | Required for _Write-off_; optional otherwise.                         |
| **Override reason**  | Administrators only; required only when allowing a negative balance.  |

The unit cost is **not** entered — it is snapshotted automatically from the
supplier item's contract price. The form shows the unit cost and current balance
beside the submit button as you select a supplier item.

After a successful save, a toast confirms _"Transaction recorded."_ and the form
resets, ready for the next entry.

---

## The six movement types, with examples

### 1. Received (+)

Use when stock arrives from a supplier. Requires a batch number and expiration
date.

| Field            | Example value                             |
| ---------------- | ----------------------------------------- |
| Movement type    | `Received`                                |
| Supplier item    | `Zuellig Pharma Corporation — CONMED0367` |
| Quantity         | `100`                                     |
| Transaction date | `2026-07-01`                              |
| Batch number     | `BATCH-2026-001`                          |
| Expiration date  | `2026-12-31`                              |

Result: balance increases by 100. A new batch `BATCH-2026-001` is created and
appears on the Batches screen.

### 2. Consumption (−)

Use when stock is issued to a ward. The ward is optional.

| Field            | Example value                             |
| ---------------- | ----------------------------------------- |
| Movement type    | `Consumption`                             |
| Supplier item    | `Zuellig Pharma Corporation — CONMED0367` |
| Quantity         | `20`                                      |
| Transaction date | `2026-07-05`                              |
| Ward             | `Pediatric Ward`                          |

Result: balance decreases by 20.

### 3. Return from Ward (+)

Use when a ward returns unused stock. A ward is required.

| Field            | Example value                             |
| ---------------- | ----------------------------------------- |
| Movement type    | `Return from Ward`                        |
| Supplier item    | `Zuellig Pharma Corporation — CONMED0367` |
| Quantity         | `5`                                       |
| Transaction date | `2026-07-10`                              |
| Ward             | `Intensive Care Unit (ICU)`               |

Result: balance increases by 5.

### 4. Return to Supplier (−)

Use when stock goes back to the supplier (for example, a recall or over-delivery).

| Field            | Example value                             |
| ---------------- | ----------------------------------------- |
| Movement type    | `Return to Supplier`                      |
| Supplier item    | `Zuellig Pharma Corporation — CONMED0367` |
| Quantity         | `10`                                      |
| Transaction date | `2026-07-12`                              |

Result: balance decreases by 10.

### 5. Transfer to Pharmacy (−)

Use when stock leaves the department's custody for the pharmacy.

| Field            | Example value                             |
| ---------------- | ----------------------------------------- |
| Movement type    | `Transfer to Pharmacy`                    |
| Supplier item    | `Zuellig Pharma Corporation — CONMED0367` |
| Quantity         | `15`                                      |
| Transaction date | `2026-07-15`                              |

Result: balance decreases by 15.

### 6. Write-off (expired/damaged) (−)

Use when stock is discarded because it expired or was damaged. A **remark is
required**.

| Field            | Example value                             |
| ---------------- | ----------------------------------------- |
| Movement type    | `Write-off (expired/damaged)`             |
| Supplier item    | `Zuellig Pharma Corporation — CONMED0367` |
| Quantity         | `8`                                       |
| Transaction date | `2026-07-20`                              |
| Remark           | `Expired for pull-out`                    |

Result: balance decreases by 8, and the remark appears in the report's remarks
section.

---

## The balance table

Below the form, the balance table lists every active supplier item with its
current balance for the open period. Click an **item code** to open that item's
**stock ledger** and see the running balance behind it.

---

## Rules the system enforces

- **No active contract price** — a transaction cannot be recorded against a
  supplier item with no active contract price, or one whose price is not yet in
  effect.
- **Closed period** — a transaction cannot be dated inside a closed period.
- **Negative balance** — a movement that would drive the balance below zero is
  rejected, unless an administrator overrides it with a reason (see below).
- **Immutability** — once saved, a transaction cannot be edited or deleted.
  Corrections are made by **reversing** it (see _Reversing Transactions_).

---

## Overriding a negative balance (administrators)

If a movement would drive the balance below zero, the system blocks it. An
administrator can override the check:

1. Enter the movement as usual.
2. In the **Override reason** field, type why the negative balance is acceptable,
   e.g. `Backdated receipt not yet entered`.
3. Click **Record movement**.

The override is recorded on the transaction and surfaced on the dashboard as an
**Overrides to review** alert.

> **Caution:** Overrides are high-impact. Use them only to correct genuine data
> errors, and always give a clear reason.

---

## Tips for fast entry

- The date defaults to today, so most entries need no date change.
- The form stays open and resets after each save, so you can record a run of
  movements quickly.
- The unit cost and current balance are shown as you pick a supplier item, so you
  can sanity-check before saving.
