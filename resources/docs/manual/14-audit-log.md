# Audit Log

The **Audit Log** is a read-only record of who did what, and when. It is
available to **administrators** only.

Open it from **Audit log** in the sidebar.

---

## What is logged

Every create, edit, delete, reversal, period close and period reopen is recorded
with:

| Field           | Meaning                                                                                 |
| --------------- | --------------------------------------------------------------------------------------- |
| **Timestamp**   | When the action happened.                                                               |
| **Profile**     | Who performed it.                                                                       |
| **Action**      | Create, Update, Delete, Reverse, Close or Reopen.                                       |
| **Record type** | The kind of record affected (supplier, item, transaction, batch, period, profile, ...). |
| **Record**      | A label identifying the affected record.                                                |
| **Changes**     | The before/after values that differ.                                                    |

---

## The log list

The log is paginated (50 rows per page), newest first. Each row shows the
timestamp, profile, action badge, record type and the changed fields.

| Timestamp        | Profile      | Action | Record type   | Changes                      |
| ---------------- | ------------ | ------ | ------------- | ---------------------------- |
| 2026-07-31 16:20 | Jose Rizal   | Close  | Period        | status: open → closed        |
| 2026-07-20 09:05 | Maria Santos | Create | Transaction   | type: write_off, quantity: 8 |
| 2026-07-01 08:00 | Ana Cruz     | Create | Supplier item | price: 1250.00               |

---

## Filtering the log

Use the filters at the top to narrow the list:

| Filter          | Example        |
| --------------- | -------------- |
| **Profile**     | `Maria Santos` |
| **Record type** | `Transaction`  |
| **Action**      | `Reverse`      |
| **From**        | `2026-07-01`   |
| **To**          | `2026-07-31`   |

Filters combine, so you can, for example, show every **Reverse** action by
**Ana Cruz** in July 2026. The filters are kept in the URL, so a filtered view
can be bookmarked or shared.

---

## Why the audit log matters

Because the system is passwordless, the audit log is the primary accountability
record. It answers questions such as:

- Who changed a contract price, and when?
- Who closed July 2026?
- Which transactions were reversed, and why?
- Who overrode a negative-balance check?

---

## Deleted profiles

If a profile is deleted, its past actions still appear in the log — the profile
name is preserved so history remains readable.
