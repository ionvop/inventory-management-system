# Items

**Items** are the distinct products/SKUs the department stocks. Managing the
item catalog is an **administrator** task.

Open it from **Items** in the sidebar.

---

## The item list

The screen lists every item, ordered by code, with its **description**, **unit
of measure** and whether it is **active**.

| Code       | Description         | Unit | Active |
| ---------- | ------------------- | ---- | ------ |
| CONMED0367 | Alitraq, 76g sachet | sach | Yes    |
| CONMED0412 | Ensure Gold 1.6 kg  | can  | Yes    |

---

## Adding an item

1. Click **Add item**.
2. Fill in the form:

    | Field           | Example value         |
    | --------------- | --------------------- |
    | **Code**        | `CONMED0367`          |
    | **Description** | `Alitraq, 76g sachet` |
    | **Unit**        | `sach`                |
    | **Active**      | checked               |

3. Click **Save**. A toast confirms _"Item created."_

Add the second example item the same way:

| Code         | Description          | Unit  |
| ------------ | -------------------- | ----- |
| `CONMED0412` | `Ensure Gold 1.6 kg` | `can` |

---

## Editing an item

1. Click the **pencil** icon on the item's row.
2. Change the code, description, unit or active flag.
3. Click **Save**. A toast confirms _"Item updated."_

---

## Deactivating an item

Deactivating keeps an item out of new transactions while preserving its history.

1. Click the **pencil** icon on the item's row.
2. Clear the **Active** checkbox.
3. Click **Save**.

---

## Deleting an item

1. Click the **trash** icon on the item's row.
2. Confirm the deletion.

Deleting an item is a **soft delete** — it is removed from the list but its
history is preserved. Prefer **deactivating** over deleting.

---

## Units of measure

The unit is a short label shown on the report and in transaction forms. Common
values in this department are:

| Unit   | Meaning |
| ------ | ------- |
| `sach` | sachet  |
| `can`  | can     |
| `bot`  | bottle  |

---

## Next step: link items to suppliers

An item on its own cannot be transacted against. You must link it to a supplier
at a contract price — see **Supplier Items**.
