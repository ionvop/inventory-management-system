# Introduction

Welcome to the **Nutrition & Dietetics Stock In/Out Inventory Management System**.
This manual is a complete, end-to-end guide to every screen and every task in the
system. It is written for the department's staff, supervisors and administrators,
and every example uses the same set of sample data so you can follow along from
start to finish.

> **Tip:** You can read the sections in order as a training course, or jump
> straight to the topic you need from the **Contents** list on the left.

---

## What the system does

The system replaces the department's Excel-based monthly stock ledger. It:

- Keeps a catalog of **suppliers**, **items** and **contract prices**.
- Tracks each delivery **batch**, including its batch number and expiration date.
- Records every stock movement as a discrete, timestamped **transaction**.
- Derives the running **balance** for every item automatically — balances are
  never typed in by hand.
- **Closes** each month, freezing its figures and carrying the ending balance
  forward as the next month's beginning balance.
- Produces the department's monthly **report** in its existing layout, exportable
  to Excel and PDF.
- Keeps an **audit trail** of who did what, and when.

---

## The three roles

Every profile has exactly one role. The role decides which screens and actions
are available.

| Role              | Who it is                                           | What they can do                                                                                                                                     |
| ----------------- | --------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Staff**         | Nutrition & Dietetics staff who log daily movements | Record all six transaction types, view batches, view the stock ledger, view the dashboard                                                            |
| **Supervisor**    | Department head / supervisor                        | Everything staff can do, plus close periods and view/export reports                                                                                  |
| **Administrator** | Catalog and system manager                          | Everything a supervisor can do, plus manage suppliers, items, supplier items and wards, reverse transactions, reopen periods, and view the audit log |

A single person may hold more than one role — the role is attached to the
**profile**, not to a separate login.

---

## Key concepts

| Term              | Meaning                                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **Profile**       | A named staff account selected at the start of a session. There is no password; selecting a profile identifies who is acting. |
| **Supplier**      | A contracted vendor, e.g. _Zuellig Pharma Corporation_.                                                                       |
| **Item**          | A distinct product/SKU, e.g. _Alitraq, 76g sachet_.                                                                           |
| **Supplier Item** | An item as offered by a specific supplier at a specific contract price. This is what transactions are recorded against.       |
| **Batch**         | A single delivery lot of a supplier item, identified by batch number and expiration date.                                     |
| **Ward**          | A hospital unit that consumes or returns stock, e.g. _Pediatric Ward_.                                                        |
| **Period**        | A calendar month that groups transactions, e.g. _July 2026_.                                                                  |
| **Transaction**   | A single stock movement (received, consumption, etc.).                                                                        |
| **Balance**       | The quantity and total cost on hand for a supplier item, always derived from transactions.                                    |

---

## The six movement types

Every transaction is one of six types. Two increase stock; four decrease it.

| Type                            | Effect on balance | Notes                                                  |
| ------------------------------- | ----------------- | ------------------------------------------------------ |
| **Received**                    | +                 | Requires a batch number and expiration date.           |
| **Return from Ward**            | +                 | Attributed to a ward.                                  |
| **Return to Supplier**          | −                 | Reduces stock without being consumption.               |
| **Transfer to Pharmacy**        | −                 | Removes stock from department custody.                 |
| **Consumption**                 | −                 | Optionally attributed to a ward.                       |
| **Write-off (expired/damaged)** | −                 | Requires a reason; typically tied to a specific batch. |

The balance formula, applied uniformly to every item, is:

```
Balance = Beginning
        + Received
        + Return from Ward
        − Return to Supplier
        − Transfer to Pharmacy
        − Consumption
        − Write-off
```

---

## The example data used in this manual

To keep every example concrete, this manual uses the following sample records.
You can create them yourself by following the sections in order.

| Kind                  | Example value                                                                  |
| --------------------- | ------------------------------------------------------------------------------ |
| Staff profile         | **Maria Santos** (Staff)                                                       |
| Supervisor profile    | **Jose Rizal** (Supervisor)                                                    |
| Administrator profile | **Ana Cruz** (Administrator)                                                   |
| Supplier              | **Zuellig Pharma Corporation** (contract status: _New contract_)               |
| Supplier              | **Distribution Solution Philippines Inc.** (contract status: _Old contract_)   |
| Item                  | **Alitraq, 76g sachet** — code `CONMED0367`, unit `sach`                       |
| Item                  | **Ensure Gold 1.6 kg** — code `CONMED0412`, unit `can`                         |
| Supplier item         | Zuellig Pharma Corporation + Alitraq @ **₱1,250.00**, effective **2026-01-01** |
| Ward                  | **Pediatric Ward**                                                             |
| Ward                  | **Intensive Care Unit (ICU)**                                                  |
| Batch                 | **BATCH-2026-001**, expires **2026-12-31**                                     |
| Period                | **July 2026**                                                                  |

---

## Where to go next

- New to the system? Start with **Getting Started**.
- Setting up the catalog? See **Suppliers**, **Items**, **Supplier Items** and
  **Wards**.
- Recording daily movements? See **Recording Transactions**.
- Closing the month? See **Periods** and **Reports**.
- Want the whole story in one place? Read the **End-to-End Walkthrough**.
