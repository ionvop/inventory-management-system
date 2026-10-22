# Inventory Settings

The **Settings** screen lets an administrator tune the thresholds the system
uses to flag stock that needs attention. The values ship with sensible defaults,
but every department works a little differently, so they can be adjusted here
without a code change.

Open it from **Settings** in the sidebar.

Changing a threshold affects what the whole department sees, so the screen is
restricted to **administrators** and every change is recorded on the audit trail.

---

## The tunable thresholds

| Setting                   | Default | What it controls                                                                             |
| ------------------------- | ------- | -------------------------------------------------------------------------------------------- |
| **Near-expiry window**    | 90 days | A batch is flagged as near expiry this many days before its expiration date (FR-3.2).        |
| **Low-stock threshold**   | 10      | An active supplier item at or below this running quantity is surfaced as low stock (FR-5.2). |
| **Bid reorder threshold** | 1,000   | A bid with this much or less remaining is flagged as needing a new bid.                      |

---

## Overriding a value

1. Type a new number into the field for the setting.
2. Click **Save settings**.

The change takes effect immediately across the dashboard, the batches screen and
the bids screen. An overridden value is marked **Overridden** so it is clear it
is no longer the default.

---

## Resetting to the default

- To clear a single override, click **Reset** on that setting's row. The row
  goes back to its configured default.
- To clear every override at once, click **Reset all**.

A blank field is ignored when you save, so clearing a value is always an
explicit reset rather than an accidental blank.
