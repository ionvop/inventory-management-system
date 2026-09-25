Software Requirements Specification
===================================

Nutrition & Dietetics Stock In/Out Inventory Management System
--------------------------------------------------------------

**Version:** 1.0 **Date:** September 19, 2026 **Prepared for:** Nutrition and Dietetics Department, Davao Regional Medical Center **Status:** Draft for review

1\. Introduction
----------------

### 1.1 Purpose

This document specifies the requirements for an Inventory Management System ("the System") that replaces the department's current Excel-based monthly stock ledger. The System records stock in/out transactions for enteral and specialized nutrition formulas supplied by multiple contracted vendors, maintains a running balance per item, and produces the department's monthly "Issuance of Drugs and Medicines and Supply Consignment" report.

### 1.2 Scope

The System will:

*   Maintain a catalog of suppliers, items, and contract prices.
    
*   Track individual delivery batches, including batch number and expiration date.
    
*   Record every stock movement (receiving, consumption/issuance, ward returns, supplier returns, transfers to pharmacy, and write-offs for expired or damaged stock) as a discrete, timestamped transaction.
    
*   Automatically compute running and monthly balances from recorded transactions, removing the manual formula-copying that causes errors in the current spreadsheet.
    
*   Close monthly periods and carry ending balances forward as the next period's beginning balances.
    
*   Generate the monthly summary report in the department's existing layout, grouped by supplier, with subtotals and a remarks section.
    
*   Provide a passwordless, profile-based method of identifying which staff member is performing each action, for accountability and sign-off purposes.
    

Out of scope for version 1.0: integration with hospital pharmacy or HIS systems, barcode/RFID scanning, multi-site (multi-facility) operation, and public internet access.

### 1.3 Intended Audience

Department administrators, IT staff responsible for deployment, and the development team building the Laravel + React application.

### 1.4 Definitions and Abbreviations

| Term | Meaning |
| --- | --- |
| SRS | Software Requirements Specification |
| Item | A distinct product/SKU (e.g., "Alitraq, 76g sachet") |
| Supplier Item | An item as offered by a specific supplier, at a specific contract price |
| Batch | A single delivery lot of a supplier item, identified by batch number and expiration date |
| Ward | A hospital unit that consumes or returns stock |
| Profile | A named staff account selected at the start of a session, used in place of a username/password login |
| LAN | Local Area Network |
| SPA | Single-Page Application |

### 1.5 References

*   Source document analyzed: MNF\_INVENTORY\_REPORT\_2026.xlsx (department's Jan–Jul 2026 monthly ledger)
    
*   Prior analysis: workflow and data-model review of the July 2026 report format
    

2\. Overall Description
-----------------------

### 2.1 Product Perspective

The System is a new, standalone, locally hosted web application. It replaces manual entry into a shared spreadsheet with a browser-based application running on the department's local network. It is not a component of any existing hospital system in this version.

### 2.2 Product Functions (Summary)

1.  Profile-based session identification (passwordless)
    
2.  Supplier and item catalog management
    
3.  Batch registration on receipt of stock
    
4.  Transaction recording for all six movement types
    
5.  Automatic running-balance calculation
    
6.  Monthly period closing and carry-forward
    
7.  Monthly report generation and export
    
8.  Audit trail of who recorded what, and when
    

### 2.3 User Classes and Characteristics

| User class | Description | Typical tasks |
| --- | --- | --- |
| Staff (encoder) | Nutrition & Dietetics staff who log daily transactions | Record receiving, consumption, returns, transfers |
| Department Head / Supervisor | Reviews and closes monthly periods, signs off reports | Approve month-end close, review discrepancies, export reports |
| Administrator | Manages suppliers and item catalog | Maintain catalog and contract prices |

A single person may hold more than one role; role is attached to the profile, not to a separate login credential.

### 2.4 Operating Environment

*   **Deployment:** Single on-premise server (or a designated workstation acting as server) connected to the department's local network. No public internet exposure is required or expected.
    
*   **Backend:** Laravel (PHP), running under a standard web server (Nginx or Apache with PHP-FPM), backed by a relational database (MySQL/MariaDB or PostgreSQL).
    
*   **Frontend:** React SPA, served by the Laravel application (via Vite build) or as static assets behind the same web server.
    
*   **Clients:** Desktop/laptop browsers on the local network (Chrome, Edge, or Firefox, current versions). Tablet use for data entry stations is a desirable but non-mandatory target.
    

### 2.5 Design and Implementation Constraints

*   Must run entirely on the local network; no dependency on external cloud services for core functionality.
    
*   Must use Laravel for the backend and React for the frontend, per the stated technology decision.
    
*   Authentication is passwordless by design (see Section 3.1) — this is a deliberate constraint, not a gap, but it shapes the security and audit requirements in Sections 4 and 5.
    
*   Must preserve the existing report's visual grouping (by supplier) and column set so staff can adopt it without retraining on the report format itself.
    

### 2.6 Assumptions and Dependencies

*   The local network is reasonably trusted (i.e., only department workstations and authorized devices can reach the server); the System is not designed to withstand hostile actors on the same network.
    
*   A designated device (server or NAS-class machine) is available to host the application continuously during working hours, with a backup/restore process owned by the facility's IT staff.
    
*   Historical data (Jan–Jul 2026) may need a one-time import from the existing spreadsheet; this is addressed as a migration task, not a recurring feature.
    

3\. Specific Requirements
-------------------------

### 3.1 User Identification — Passwordless Profile Picker

This replaces traditional username/password login.

**FR-1.1** On opening the application, the System shall display a profile-picker screen showing all non-deleted staff profiles as selectable cards (name and optional avatar/initials), similar to a media-service "who's watching" screen.

**FR-1.2** Selecting a profile shall start a session attributed to that profile, with no password or PIN required, and shall return the user to the department's main workspace (dashboard).

**FR-1.3** The profile-picker screen shall expose **Add**, **Edit**, and **Delete** controls directly on the interface (e.g., an "Manage profiles" toggle revealing pencil/trash icons on each card, and an "add profile" card), without navigating to a separate admin panel.

**FR-1.4** Add/Edit/Delete controls on the profile picker shall be available to any user regardless of role; no administrator role or selected profile is required to manage profiles. This also allows the very first profile to be created on a fresh installation.

**FR-1.5** Creating or editing a profile shall capture at minimum: display name and role (Staff / Supervisor / Administrator). Deleting a profile shall be a soft delete that removes it from the profile picker while preserving that profile's history on past transactions; hard deletion of a profile with existing transaction history shall not be permitted.

**FR-1.6** The System shall record the active profile on every transaction, catalog change, and period-close action, to preserve the "Prepared by / Received by" accountability that the current paper-based report requires.

**FR-1.7** Switching profiles ("log out" equivalent) shall be available from the main workspace at any time and shall return to the profile-picker screen.

> **Design note carried over from analysis:** because there is no password, the profile picker is an identification mechanism, not an authentication mechanism. Section 5.2 (Security) states the compensating controls this implies.

### 3.2 Supplier and Item Catalog Management

**FR-2.1** Administrators shall be able to create, edit, and deactivate **Suppliers** (name, contract status such as new/old contract, active flag).

**FR-2.2** Administrators shall be able to create, edit, and deactivate **Items** (item code, description, unit of measure).

**FR-2.3** Administrators shall be able to create a **Supplier Item** linking a Supplier and an Item at a specific **contract price**, with an effective date. Changing a contract price shall create a new priced record rather than overwriting history, so past transactions retain the price that was in effect when they occurred.

**FR-2.4** The System shall prevent recording a transaction against a Supplier Item that has no active contract price.

### 3.3 Batch Management

**FR-3.1** When stock is received, the System shall require (where applicable) a **batch number** and **expiration date** for the delivered quantity, creating a Batch record linked to the relevant Supplier Item.

**FR-3.2** The System shall allow a batch to be flagged with a status (Active, Near-expiry, Expired, Damaged/Write-off) and shall automatically flag a batch as **Near-expiry** a configurable number of days before its expiration date (default 90 days).

**FR-3.3** The System shall display a Near-expiry / Expired dashboard view so staff can identify stock for pull-out before it lapses, addressing the "expired for pull-out" and "damage for pull-out" cases seen in the current report's remarks column.

### 3.4 Transaction Recording

**FR-4.1** The System shall support recording the following transaction types against a Supplier Item (and, where relevant, a specific Batch):

| Type | Effect on balance | Notes |
| --- | --- | --- |
| Received | + | Requires batch no. and expiration date |
| Consumption | − | Optionally attributed to a Ward |
| Return from Ward | + | Attributed to a Ward; does not affect supplier balance |
| Return to Supplier | − | Reduces stock without being consumption |
| Transfer to Pharmacy | − | Removes stock from department custody |
| Write-off (expired/damaged) | − | Requires a reason/remark; typically tied to a specific Batch |

**FR-4.2** Every transaction shall record: Supplier Item, Batch (if applicable), transaction type, quantity, unit cost (snapshotted from the Supplier Item's contract price at the time of the transaction), computed total cost, transaction date, recording profile, optional Ward, and optional free-text remark.

**FR-4.3** The System shall reject a transaction that would drive an item's running quantity below zero, unless an Administrator explicitly overrides the check (with the override reason logged).

**FR-4.4** Transaction quantity and cost fields shall not be manually overridable once saved; corrections shall be made via a reversing/adjustment transaction that references the original, preserving an audit trail (no silent edits to historical figures).

### 3.5 Balance Calculation

**FR-5.1** The System shall compute an item's current balance as: Beginning Balance (for the open period) + Received + Return from Ward − Return to Supplier − Transfer to Pharmacy − Consumption − Write-off, applied uniformly by a single calculation routine for every item — eliminating the per-row formula inconsistency present in the source spreadsheet.

**FR-5.2** The System shall display, for any item, a live running balance (quantity and total cost) reflecting all transactions recorded so far in the open period.

**FR-5.3** The System shall never require or accept a manually entered "ending balance" — it is always derived, never keyed in.

### 3.6 Monthly Period Closing

**FR-6.1** The System shall organize transactions into monthly periods per the department's existing reporting cadence.

**FR-6.2** A Supervisor or Administrator shall be able to **close** a period. Closing shall: a. Freeze all transactions dated within that period against further edits; b. Snapshot each Supplier Item's ending quantity and cost as that period's closing balance; c. Automatically create the following period's beginning balance from the closing snapshot, per item.

**FR-6.3** The System shall prevent closing a period while any item shows a negative computed balance, surfacing the offending rows for correction first.

**FR-6.4** Re-opening a closed period shall be restricted to Administrators and shall require a logged reason.

### 3.7 Reporting

**FR-7.1** The System shall generate a monthly report matching the department's existing layout: rows grouped by supplier, columns for Item, Unit, Batch No., Expiration Date, Contract Price, and each movement type (Beginning, Received, Return from Ward, Return to Supplier, Transfer to Pharmacy, Consumption, Ending Balance), each with quantity and total-cost sub-columns, plus a per-supplier subtotal and a grand subtotal row.

**FR-7.2** The report shall include a Remarks section listing notable events for the period (e.g., pull-outs for expiry or damage, pending replacements), sourced from write-off transactions and batch status flags rather than free-typed at report time.

**FR-7.3** The report shall be exportable to PDF and to Excel (.xlsx), and shall show the names of the profiles who prepared and (optionally) reviewed/approved it, replacing the blank "Prepared by / Received by" signature lines with recorded profile attributions and timestamps.

**FR-7.4** The System shall allow regenerating the report for any closed period on demand, always producing the same figures from the immutable closed-period snapshot.

### 3.8 Audit Trail

**FR-8.1** The System shall log, for every create/edit/delete action on catalog data, transactions, batches, and profiles: the acting profile, timestamp, and a before/after value where applicable.

**FR-8.2** Audit logs shall be viewable by Administrators, filterable by profile, date range, and record type.

4\. External Interface Requirements
-----------------------------------

### 4.1 User Interfaces

*   Profile-picker screen (Section 3.1) as the application's entry point.
    
*   Main workspace: catalog management, transaction entry forms (one per transaction type, or a unified form with a type selector), a stock/balance view per item, a near-expiry dashboard, and monthly report screens.
    
*   The transaction entry screen shall be optimized for fast repeated entry (e.g., keep the form open after save, default the date to today, remember the last-used supplier).
    

### 4.2 Hardware Interfaces

None required for version 1.0. Standard keyboard/mouse/touchscreen input on client devices.

### 4.3 Software Interfaces

*   Laravel backend exposing a REST (or Inertia-based) API consumed by the React frontend.
    
*   Relational database (MySQL/MariaDB or PostgreSQL) for persistence.
    
*   PDF generation library (e.g., a Laravel-compatible PDF package) and an Excel export library for report output.
    

### 4.4 Communications Interfaces

*   HTTP(S) over the local network. A self-signed certificate or internal reverse proxy for HTTPS is recommended even on a LAN, to protect session cookies in transit, but is not a hard blocker for version 1.0 given the closed-network deployment.
    

5\. Non-Functional Requirements
-------------------------------

### 5.1 Performance

*   NFR-1.1: Transaction entry form submission shall complete in under 1 second under normal LAN conditions with up to ~20 concurrent users.
    
*   NFR-1.2: Monthly report generation shall complete in under 5 seconds for a period containing up to 1,000 transactions.
    

### 5.2 Security (within a passwordless, LAN-only design)

*   NFR-2.1: Because profile selection alone identifies a user, physical and network access control is the primary safeguard — the deploying facility is responsible for restricting network access to authorized department devices (e.g., via network segmentation or firewall rules), and the application shall not be exposed beyond the local network.
    
*   NFR-2.2: High-impact actions (catalog price changes, period re-opening, negative-balance overrides) shall always be attributed to the acting profile and logged, since these are the actions with the greatest downstream impact in a passwordless system. Profile management (add/edit/delete) is intentionally open to all users and shall likewise be attributed to the acting profile and logged.
    
*   NFR-2.3: The System should support an optional, configurable idle-session timeout that returns to the profile picker after a period of inactivity, to reduce the risk of one staff member's actions being misattributed after they step away.
    
*   NFR-2.4: The application shall validate and sanitize all input server-side regardless of client-side validation, per standard Laravel practice.
    

### 5.3 Reliability and Data Integrity

*   NFR-3.1: All balance figures shall be derived at read time (or via a verifiable recomputation) from the transaction log, never stored as an independently editable field, so historical figures can always be reconciled against raw transactions.
    
*   NFR-3.2: The database shall be backed up on a regular schedule (daily recommended) by the hosting facility's IT staff; the System should provide a manual "export full backup" action for Administrators as a convenience.
    

### 5.4 Usability

*   NFR-4.1: Staff already familiar with the spreadsheet's column layout shall be able to locate equivalent fields in the new transaction forms and report without additional documentation, per the layout continuity required in FR-7.1.
    
*   NFR-4.2: The profile picker and its management controls shall require no more than two clicks to add a new profile.
    

### 5.5 Maintainability

*   NFR-5.1: The System shall follow Laravel's standard MVC/service-layer conventions and React's component conventions, to keep the codebase approachable for future maintainers.
    
*   NFR-5.2: Business rules that are currently spreadsheet formulas (e.g., balance calculation in Section 3.5) shall live in a single, tested backend service/class, not duplicated across the frontend and backend.
    

### 5.6 Availability

*   NFR-6.1: The System should be available during the department's operating hours; scheduled maintenance windows outside those hours are acceptable and do not require redundancy/failover infrastructure for version 1.0.
    

6\. Data Migration
------------------

**MIG-1** A one-time import utility shall be provided to load historical data from the existing MNF\_INVENTORY\_REPORT\_2026.xlsx file (Jan–Jul 2026), mapping each month's sheet into Supplier, Item, Supplier Item, Batch (where batch/expiry data exists, i.e., July onward), and Transaction records, and establishing the correct beginning balance for the first "live" month in the new System.

**MIG-2** Discrepancies encountered during import (e.g., the known formula error in the July "Transfer to Pharmacy" cost for Supportan, or the missing return-from-ward term for Nepro HP/LP in earlier months) shall be surfaced in an import report for manual review rather than imported silently, since these are known data-quality issues in the source file.

7\. Future Considerations (Out of Scope for v1.0)
-------------------------------------------------

*   Barcode/QR scanning at receiving and issuance
    
*   Integration with hospital pharmacy or HIS systems
    
*   Multi-facility/multi-department support
    
*   Optional step-up authentication (e.g., PIN) for high-impact actions, if the passwordless model is later found insufficient
    
*   Automated low-stock and near-expiry email/SMS alerts
    

8\. Appendix A — Requirements Traceability to Source Report
-----------------------------------------------------------

| Report element (July format) | Corresponding requirement |
| --- | --- |
| Item code, description, unit, batch no., expiration date, contract price | FR-2.1–2.4, FR-3.1 |
| Beginning / Received / Return from Wards / Return to Supplier / Transfer to Pharmacy / Consumption / Ending columns | FR-4.1, FR-5.1 |
| Per-supplier grouping and subtotal, grand subtotal | FR-7.1 |
| Inventory Remarks (pull-outs, pending replacements) | FR-3.2, FR-3.3, FR-7.2 |
| Prepared by / Received by | FR-1.6, FR-7.3 |