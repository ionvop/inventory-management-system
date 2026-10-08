# Getting Started

This section covers the first thing you see when you open the system: the
**profile picker**. There is no username or password — you identify yourself by
selecting a profile.

---

## Opening the system

1. Open the system's address in your browser (Chrome, Edge or Firefox).
2. The **profile picker** appears, showing every active profile as a card.

If no profiles exist yet (a fresh installation), the picker opens directly in
**managing mode** so you can create the very first profile.

---

## Selecting a profile

1. On the profile picker, find your card — for example, **Maria Santos**.
2. Click the card.
3. You are taken to the **Dashboard**, and the top-right header now shows your
   name and role.

> **Note:** Because there is no password, the profile picker _identifies_ you
> rather than _authenticates_ you. Always switch profiles when you step away so
> your actions are not misattributed.

---

## Adding a profile

Anyone can add a profile — no administrator role is required. This is also how
the first profile is created on a fresh installation.

1. On the profile picker, click **Manage profiles**.
2. Click the **Add profile** card.
3. Fill in the form:

    | Field    | Example value  |
    | -------- | -------------- |
    | **Name** | `Maria Santos` |
    | **Role** | `Staff`        |

4. Click **Save**. The new card appears on the picker.

Repeat to create the other example profiles:

| Name         | Role          |
| ------------ | ------------- |
| `Jose Rizal` | Supervisor    |
| `Ana Cruz`   | Administrator |

---

## Editing a profile

1. Click **Manage profiles**.
2. Click the **pencil** icon on the profile's card.
3. Change the name or role, then click **Save**.

For example, if Maria is promoted, edit her card and change her role from
**Staff** to **Supervisor**.

---

## Deleting a profile

1. Click **Manage profiles**.
2. Click the **trash** icon on the profile's card.
3. Confirm the deletion.

Deleting a profile is a **soft delete**: the profile disappears from the picker
but its history on past transactions is preserved. A profile that has recorded
transactions cannot be hard-deleted.

---

## Switching profiles (logging out)

1. In the top-right header, click **Switch profile**.
2. You are returned to the profile picker.

Use this whenever a different staff member takes over the workstation.

---

## Changing the appearance

The **Appearance** toggle in the header (and on the profile picker) switches
between light and dark mode. Your choice is remembered on this device.

---

## What you see after signing in

Once a profile is selected, the left sidebar shows the screens available to your
role:

| Nav item       | Staff | Supervisor | Administrator |
| -------------- | :---: | :--------: | :-----------: |
| Dashboard      |   ✓   |     ✓      |       ✓       |
| Transactions   |   ✓   |     ✓      |       ✓       |
| Batches        |   ✓   |     ✓      |       ✓       |
| Periods        |       |     ✓      |       ✓       |
| Reports        |       |     ✓      |       ✓       |
| Suppliers      |       |            |       ✓       |
| Items          |       |            |       ✓       |
| Supplier Items |       |            |       ✓       |
| Wards          |       |            |       ✓       |
| Audit log      |       |            |       ✓       |
| User Manual    |   ✓   |     ✓      |       ✓       |

On a phone or narrow window, the sidebar collapses into a menu button in the
header.
