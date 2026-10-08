# Wards

**Wards** are the hospital units that consume or return stock. They are used to
attribute **Consumption** and **Return from Ward** transactions. Managing wards
is an **administrator** task.

Open it from **Wards** in the sidebar.

---

## The ward list

The screen lists every ward, ordered by name, with its active flag and whether it
has any transactions.

| Name                      | Active | Has transactions |
| ------------------------- | ------ | ---------------- |
| Intensive Care Unit (ICU) | Yes    | No               |
| Pediatric Ward            | Yes    | No               |

---

## Adding a ward

1. Click **Add ward**.
2. Fill in the form:

    | Field      | Example value    |
    | ---------- | ---------------- |
    | **Name**   | `Pediatric Ward` |
    | **Active** | checked          |

3. Click **Save**. A toast confirms _"Ward created."_

Add the second example ward the same way:

| Name                        |
| --------------------------- |
| `Intensive Care Unit (ICU)` |

---

## Editing a ward

1. Click the **pencil** icon on the ward's row.
2. Change the name or active flag.
3. Click **Save**. A toast confirms _"Ward updated."_

---

## Deactivating a ward

1. Click the **pencil** icon on the ward's row.
2. Clear the **Active** checkbox.
3. Click **Save**.

A deactivated ward no longer appears in the transaction form, but past
transactions that reference it are unchanged.

---

## Deleting a ward

1. Click the **trash** icon on the ward's row.
2. Confirm the deletion.

Deletion is **blocked** when the ward already has transactions, because those
transactions must keep their ward attribution. Deactivate it instead.

> **Note:** Unlike suppliers and items, wards are **hard-deleted** when they have
> no transactions. This is why the list shows a **Has transactions** column — it
> tells you at a glance whether a ward can be deleted.

---

## Where wards appear

- **Consumption** transactions may optionally be attributed to a ward.
- **Return from Ward** transactions must be attributed to a ward.
- The ward name appears on the stock ledger and in the report.
