# Batches

The **Batches** screen shows every delivery batch and its expiry status, so staff
can pull out stock before it lapses. It is available to every role, since staff
perform the pull-outs.

Open it from **Batches** in the sidebar.

---

## The batch list

Batches are ordered by expiration date, soonest first. Each row shows the batch
number, expiration date, days until expiry, derived status, supplier and item.

| Batch no.      | Expiration | Days until expiry | Status | Supplier                   | Item       |
| -------------- | ---------- | ----------------- | ------ | -------------------------- | ---------- |
| BATCH-2026-001 | 2026-12-31 | in 176 days       | Active | Zuellig Pharma Corporation | CONMED0367 |

---

## How status is derived

A batch's status is calculated from its expiration date and the near-expiry
window (default **90 days**), not typed in by hand:

| Status          | When it applies                                       |
| --------------- | ----------------------------------------------------- |
| **Expired**     | The expiration date has passed.                       |
| **Near expiry** | The expiration date is within the near-expiry window. |
| **Active**      | The expiration date is further away than the window.  |
| **Damaged**     | The batch has been manually flagged as damaged.       |

The near-expiry window is configurable (default 90 days) and is shown on the
dashboard.

---

## Summary counts

At the top of the screen, a summary shows how many batches fall into each status
— expired, near expiry, active and damaged — so you can see the pull-out workload
at a glance.

---

## Flagging a batch as damaged

When a batch is damaged (for example, a torn carton or a broken seal), flag it so
it is pulled out and appears in the report remarks.

1. Find the batch's row.
2. Click **Flag as damaged**.
3. In the dialog, enter the reason, e.g. `Damaged in transit`.
4. Confirm.

The batch's status becomes **Damaged** and the reason is stored. A batch that is
already damaged cannot be flagged again.

> **Note:** Flagging a batch as damaged does **not** change the balance by
> itself. To remove the damaged stock from the balance, also record a
> **Write-off** transaction (see _Recording Transactions_).

---

## Pull-out workflow

A typical pull-out for an expired or damaged batch:

1. Open **Batches** and find the batch (use the summary counts to spot expired
   and near-expiry stock).
2. If the stock is damaged, click **Flag as damaged** and give a reason.
3. Go to **Transactions** and record a **Write-off** for the affected quantity,
   with a remark such as `Expired for pull-out` or `Damaged for pull-out`.
4. The write-off reduces the balance and adds a line to the report's remarks.

---

## Where batches appear

- **Received** transactions create a batch automatically.
- The batch number and expiration date appear on the report.
- Damaged and expired batches feed the report's remarks section.
