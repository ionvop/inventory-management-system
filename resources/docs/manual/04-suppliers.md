# Suppliers

**Suppliers** are the contracted vendors the department buys from. Managing the
supplier catalog is an **administrator** task.

Open it from **Suppliers** in the sidebar.

---

## The supplier list

The screen lists every supplier, ordered by name, with its **contract status**
and whether it is **active**.

| Name                                   | Contract status | Active |
| -------------------------------------- | --------------- | ------ |
| Distribution Solution Philippines Inc. | Old contract    | Yes    |
| Zuellig Pharma Corporation             | New contract    | Yes    |

---

## Adding a supplier

1. Click **Add supplier**.
2. Fill in the form:

    | Field               | Example value                |
    | ------------------- | ---------------------------- |
    | **Name**            | `Zuellig Pharma Corporation` |
    | **Contract status** | `New contract`               |
    | **Active**          | checked                      |

3. Click **Save**. A green toast confirms _"Supplier created."_

Add the second example supplier the same way:

| Name                                     | Contract status |
| ---------------------------------------- | --------------- |
| `Distribution Solution Philippines Inc.` | `Old contract`  |

---

## Editing a supplier

1. Click the **pencil** icon on the supplier's row.
2. Change the name, contract status or active flag.
3. Click **Save**. A toast confirms _"Supplier updated."_

For example, when a contract is renewed, change Zuellig's contract status from
_New contract_ to _Old contract_.

---

## Deactivating a supplier

Deactivating keeps a supplier out of new transactions while preserving its
history. It is the normal way to retire a supplier.

1. Click the **pencil** icon on the supplier's row.
2. Clear the **Active** checkbox.
3. Click **Save**.

A deactivated supplier no longer appears when recording transactions, but its
past transactions and reports are unchanged.

---

## Deleting a supplier

1. Click the **trash** icon on the supplier's row.
2. Confirm the deletion.

Deleting a supplier is a **soft delete** — it is removed from the list but its
history is preserved. Prefer **deactivating** over deleting so the record stays
visible for reference.

---

## Why contract status matters

The contract status is a free-text label (for example _New contract_ or _Old
contract_) used to distinguish current vendors from legacy ones. It appears on
the supplier list and helps administrators decide which suppliers to keep active.
