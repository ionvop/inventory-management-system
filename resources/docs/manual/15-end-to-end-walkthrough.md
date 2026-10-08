# End-to-End Walkthrough

This section ties everything together in one continuous scenario. It follows the
department through a full month — **July 2026** — from a fresh installation to a
signed-off, exported report. Every value matches the example data used elsewhere
in this manual.

Follow along in order, or use it as a checklist for your first month on the
system.

---

## Cast

| Profile      | Role          |
| ------------ | ------------- |
| Maria Santos | Staff         |
| Jose Rizal   | Supervisor    |
| Ana Cruz     | Administrator |

---

## Step 1 — Create the profiles

On the profile picker, click **Manage profiles** and add:

| Name         | Role          |
| ------------ | ------------- |
| Maria Santos | Staff         |
| Jose Rizal   | Supervisor    |
| Ana Cruz     | Administrator |

See **Getting Started**.

---

## Step 2 — Set up the catalog (Ana, administrator)

Select **Ana Cruz**, then:

1. **Suppliers** → add `Zuellig Pharma Corporation` (contract status
   `New contract`) and `Distribution Solution Philippines Inc.` (`Old contract`).
2. **Items** → add `CONMED0367 — Alitraq, 76g sachet` (unit `sach`) and
   `CONMED0412 — Ensure Gold 1.6 kg` (unit `can`).
3. **Supplier Items** → link Zuellig + Alitraq at **₱1,250.00**, effective
   **2026-01-01**.
4. **Wards** → add `Pediatric Ward` and `Intensive Care Unit (ICU)`.

See **Suppliers**, **Items**, **Supplier Items** and **Wards**.

---

## Step 3 — Receive the first delivery (Maria, staff)

Switch to **Maria Santos**. Open **Transactions** and record a receipt:

| Field            | Value                                   |
| ---------------- | --------------------------------------- |
| Movement type    | Received                                |
| Supplier item    | Zuellig Pharma Corporation — CONMED0367 |
| Quantity         | 100                                     |
| Transaction date | 2026-07-01                              |
| Batch number     | BATCH-2026-001                          |
| Expiration date  | 2026-12-31                              |

This creates the **July 2026** period automatically and a batch
`BATCH-2026-001`. Balance: **100 sach / ₱125,000.00**.

---

## Step 4 — Record the month's movements (Maria)

Continue recording the rest of July:

| Date       | Type                        | Qty | Ward                      | Remark               |
| ---------- | --------------------------- | --- | ------------------------- | -------------------- |
| 2026-07-05 | Consumption                 | 20  | Pediatric Ward            |                      |
| 2026-07-10 | Return from Ward            | 5   | Intensive Care Unit (ICU) |                      |
| 2026-07-12 | Return to Supplier          | 10  |                           |                      |
| 2026-07-15 | Transfer to Pharmacy        | 15  |                           |                      |
| 2026-07-20 | Write-off (expired/damaged) | 8   |                           | Expired for pull-out |

See **Recording Transactions**.

---

## Step 5 — Check the balance (Maria)

Open **Transactions** and click the item code `CONMED0367` to open the **stock
ledger**. It shows:

| Date       | Type                 | Qty | Running qty | Running cost |
| ---------- | -------------------- | --- | ----------- | ------------ |
| 2026-07-01 | Received             | 100 | 100         | ₱125,000.00  |
| 2026-07-05 | Consumption          | 20  | 80          | ₱100,000.00  |
| 2026-07-10 | Return from Ward     | 5   | 85          | ₱106,250.00  |
| 2026-07-12 | Return to Supplier   | 10  | 75          | ₱93,750.00   |
| 2026-07-15 | Transfer to Pharmacy | 15  | 60          | ₱75,000.00   |
| 2026-07-20 | Write-off            | 8   | 52          | ₱65,000.00   |

Ending balance: **52 sach / ₱65,000.00**.

See **Stock Ledger**.

---

## Step 6 — Handle a pull-out (Maria)

The **Batches** screen shows `BATCH-2026-001` as **Active** (expiring
2026-12-31). Suppose a carton is found damaged:

1. Click **Flag as damaged** and enter `Damaged in transit`.
2. Record a **Write-off** for the damaged quantity with the remark
   `Damaged for pull-out`.

See **Batches**.

---

## Step 7 — Correct a mistake (Ana)

Suppose Maria recorded a consumption against the wrong item. Switch to **Ana
Cruz**, open **Transactions**, find the transaction and click **Reverse**. Enter
the remark `Recorded against the wrong item.`

The original and reversal net to zero, restoring the balance. See **Reversing
Transactions**.

---

## Step 8 — Close the month (Jose, supervisor)

Switch to **Jose Rizal**. Open **Periods**:

1. Confirm **July 2026** shows **0 negative items**.
2. Click **Close** on the July 2026 row.

The period is frozen, each item's ending balance is snapshotted, and the
beginning balance for August 2026 is created from it. See **Periods**.

> If the period shows negative items, correct them on **Transactions** first.

---

## Step 9 — Generate and export the report (Jose)

Open **Reports**, select **July 2026**, and review the report:

- Grouped by supplier, with beginning, each movement column, and ending.
- Per-supplier subtotals and a grand total.
- An **Inventory remarks** section listing the write-off and the damaged batch.

Then export:

1. Click **Export to Excel** → `inventory-report-2026-07.xlsx`.
2. Click **Export to PDF** → `inventory-report-2026-07.pdf`.

The **Prepared by** line shows **Jose Rizal**, who closed the period. See
**Reports**.

---

## Step 10 — Review the audit trail (Ana)

Switch to **Ana Cruz** and open **Audit log**. Filter by **Action: Close** to
confirm Jose closed July 2026, or by **Action: Reverse** to see the correction.
See **Audit Log**.

---

## Step 11 — Start August

Recording the first August transaction creates the **August 2026** period. Its
beginning balance is the **52 sach / ₱65,000.00** carried forward from July's
close, so the ledger continues seamlessly.

---

## The whole month at a glance

| Step | Who    | Screen                                     | Outcome                               |
| ---- | ------ | ------------------------------------------ | ------------------------------------- |
| 1    | Anyone | Profile picker                             | Profiles created                      |
| 2    | Ana    | Suppliers / Items / Supplier Items / Wards | Catalog ready                         |
| 3    | Maria  | Transactions                               | First receipt + batch                 |
| 4    | Maria  | Transactions                               | Month's movements recorded            |
| 5    | Maria  | Stock ledger                               | Balance verified                      |
| 6    | Maria  | Batches / Transactions                     | Damaged stock pulled out              |
| 7    | Ana    | Transactions                               | Mistake reversed                      |
| 8    | Jose   | Periods                                    | July closed, balances carried forward |
| 9    | Jose   | Reports                                    | Report reviewed and exported          |
| 10   | Ana    | Audit log                                  | Actions verified                      |
| 11   | Maria  | Transactions                               | August begins from carried balance    |
