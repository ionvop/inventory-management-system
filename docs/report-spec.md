# July-Format Monthly Report — Excel Layout Specification

**Audience:** AI agents building the Excel report generator
**Source:** sheet `JUL` in `MNF_INVENTORY_REPORT_2026.xlsx`
**Goal:** reproduce the July sheet as closely as possible: same cells, merges, widths, fonts, fills, borders, number formats, formulas and print setup.

This was measured directly from the file with openpyxl, not estimated. Anything marked **QUIRK** is an oddity in the original. Section 9 says whether to reproduce or fix it.

---

## 1. Workbook and sheet level

| Property | Value |
|---|---|
| Workbook | One file per year, one tab per month. Tabs are `JAN`, `FEB ` (trailing space in the original), `MAR`, `APR`, `MAY`, `JUN`, `JUL`. New tabs should use the 3-letter uppercase month with no trailing space. |
| Format to copy | Use **JUL only**. JAN–JUN use an older, simpler layout with no item code, batch or expiry columns. |
| Used range | `A1:T38` (20 columns; content ends at row 35, rows 36–38 are empty) |
| Freeze panes | None |
| Gridlines | Default (shown) |
| Zoom | Default |
| Orientation | Landscape |
| Paper size | Code 14 = **Folio (8.5 x 13 in)**, common in the Philippines |
| Margins (in) | Left 0.25, Right 0.00, Top 0.25, Bottom 0.25 |
| Print area, print titles, header/footer | None |
| Fit to page / scale | Not set |
| Images | None |
| Default font | Calibri 11 (workbook default). Table content is 6 pt; title rows are 10 pt bold; the remarks block uses 8 pt (label) and 10 pt (`N32`, signature labels) |
| Theme | Office 2007-2010 theme (accent4 `8064A2`, accent5 `4BACC6`) |

---

## 2. Column map (A to T)

Two kinds of columns: 6 descriptor columns (A-F) followed by 7 movement pairs of QTY / TOTAL COST (G-T).

| Col | Header (row 4 / row 5) | Width | Item-row number format | Item-row alignment | Content |
|---|---|---|---|---|---|
| A | ITEM / CODE | 8.14 | General | left, center | Item code, e.g. `CONMED0367` |
| B | ITEM DESCRIPTION | 27.14 | General | left, center | Free-text description |
| C | UNIT | 3.29 | General | left, center, wrap | `can`, `sach`, `bot` |
| D | BATCH NO. | 8.00 | General | center, center, wrap | Batch string or number (mixed types) |
| E | EXPIRATION DATE | 9.43 | `mm-dd-yy` | center, center, wrap | Date |
| F | CONTRACT PRICE | 8.71 | `0.00` | right, center | Unit price |
| G | BEGINNING BALANCE JULY 2026 / QTY. | 2.86 | `0` | right, center | Input: qty |
| H | / TOTAL COST | 10.86 | `#,##0.00` | right, center | Formula |
| I | RECEIVED JULY 2026 / QTY. | 3.43 | `0` | right, center | Input: qty |
| J | / TOTAL COST | 11.00 | `#,##0.00` | right, center | Formula |
| K | RETURN FROM DIFFERENT WARDS / QTY. | 4.00 | `0` | right, center | Input: qty |
| L | / TOTAL COST | 8.43 | `#,##0.00` | right, center | Formula |
| M | RETURN TO SUPPLIER / QTY. | 3.00 | `0` | right, center | Input: qty |
| N | / TOTAL COST | 7.57 | `0.00` | right, center | Formula |
| O | TRANSFER STOCK TO PHARMACY / QTY. | 3.57 | `0` | right, center | Input: qty |
| P | / TOTAL COST | 12.14 | `0.00` | right, center | Formula |
| Q | CONSUMPTION / QTY. | 3.00 | `0` | right, center | Input: qty |
| R | / TOTAL COST | 10.29 | `#,##0.00` | right, center | Formula |
| S | BAL. JULY 2026 / QTY. | 3.29 | `0` | right, center | Formula |
| T | / TOTAL COST | 11.00 | `#,##0.00` | right, center | Formula |

The header month text is dynamic: `BEGINNING BALANCE <MONTH> <YEAR>`, `RECEIVED <MONTH> <YEAR>`, `BAL. <MONTH> <YEAR>`. Copy the widths exactly. The QTY columns are very narrow (about 3), so the numbers depend on the 6 pt font to fit.

---

## 3. Row map

| Rows | Purpose |
|---|---|
| 1-3 | Title band, each row merged `A:T` |
| 4-5 | Two-row column header |
| 5 (also) | The first supplier band is placed in cell `B5`, inside the header row (see QUIRK below) |
| 6-29 | Item rows interleaved with supplier band rows |
| 30 | SUB TOTAL |
| 31 | Blank |
| 32 | `Inventory Remarks:` label, `=TODAY()` in `N32` |
| 33-35 | Numbered remarks (merged `B:F`), with `Prepared by:` / `Received by:` in row 34 |
| 36-38 | Empty |

### 3.1 Title rows (1-3)

| Cell | Text | Font | Fill | Alignment |
|---|---|---|---|---|
| `A1` (merged `A1:T1`) | `Davao Regional Medical center` (lowercase "c" is in the original) | Calibri 10 bold | accent4 lighter 60%, `CCC0DA` | center, center |
| `A2` (merged `A2:T2`) | `Nutrition and Dietetics Department` | same | same | same |
| `A3` (merged `A3:T3`) | ` ISSUANCE OF DRUGS AND MEDICINES AND SUPPLY CONSIGNMENT FOR THE MONTH OF : JULY 2026` (leading space; month/year dynamic) | same | same | same |

Row 1 height 12.75; rows 2-3 default (15).

### 3.2 Header rows (4-5)

**Merges:** `C4:C5`, `D4:D5`, `E4:E5`, `F4:F5`, `G4:H4`, `I4:J4`, `K4:L4`, `M4:N4`, `O4:P4`, `Q4:R4`, `S4:T4`. Note that `A4`/`A5` and `B4`/`B5` are **not** merged.

- Row 4: font 6 pt **bold**, center/center, wrap. Row height **24**. `A4` = `ITEM`, `B4` = `ITEM DESCRIPTION` (B4 has the purple fill, `CCC0DA`), `C4`-`F4` = UNIT, BATCH NO., EXPIRATION DATE, CONTRACT PRICE (white fill).
- Row 5: row height **18**. `A5` = `CODE` (bold). `G5`, `I5`, `K5`, `M5`, `O5`, `Q5`, `S5` = `QTY.`, and `H5`, `J5`, `L5`, `N5`, `P5`, `R5`, `T5` = `TOTAL COST`. These are 6 pt **regular** (not bold), center. `Q5` is right-aligned by accident.
- Borders: thin on all sides across the header; `A4`/`A5` have a **medium** left border.

### 3.3 Supplier band rows

A supplier band is a row where **only column B** has text. It is bold 6 pt, centered, and its fill covers B only. All other cells in the row are blank but carry the standard white fill, borders and number formats of an item row.

| Band | Row | Fill |
|---|---|---|
| `ZUELLIG PHARMA CORPORATION                                 MEDICAL MISSION` (with padding spaces) | 5 (`B5`) | aqua lighter 60%, `B7DEE8` |
| `ZUELLIG PHARMA CORPORATION` | 7 | aqua lighter 60%, `B7DEE8` |
| `DISTRIBUTION SOLUTION PHILIPPINES INC.` | 15 | aqua lighter 40%, `92CDDC` |
| `RBC-MDC CORPORATION` | 21 | `92CDDC` |
| `IVAXX MARKETING CORPORATION` | 24 | `92CDDC` |
| `VAXIMAXX MARKETING VENTURES, INC. ` (trailing space) | 27 | `92CDDC` |

**QUIRK:** the sheet has two Zuellig bands. The first (`B5`) sits in the header row and covers a single item (row 6, Ensure Gold 1.6 kg). The second (row 7) covers the other 7 Zuellig items (rows 8-14). Model this as **two supplier groups**: "Zuellig - Medical Mission" and "Zuellig". The first group's band lives in the header row, and later groups get their own band row.

### 3.4 Item rows

- Font 6 pt regular, thin borders on all sides, white fill (`FFFFFF`) except `B` and `F`, which have no fill.
- Row heights are mostly default (15); rows 10, 11, 12, 17, 20, 22, 23, 25, 26, 27 are 16.5, row 15 and 29-30 are 15.75, row 18 is 14.25. These are cosmetic, so keep default 15 unless exact match is required.
- Number formats and alignments are as in section 2.

### 3.5 Subtotal row (row 30)

| Cell | Value |
|---|---|
| `B30` | `SUB TOTAL` (bold) |
| `F30`, `H30`, `J30`, `L30`, `N30`, `P30`, `R30`, `T30` | `=SUM(X6:X29)` for the column X |

- Fill solid `FFFFCC` (light yellow), bold, thin borders. `A30` has medium left/top/bottom borders.
- Format: `_-[$₱-464]* #,##0.0_-;\-[$₱-464]* #,##0.0_-;_-[$₱-464]* "-"_-;_-@_-` (peso accounting). `N30` uses the same format with `#,##0` (no decimal).
- **QUIRK:** quantity columns (G, I, K, M, O, Q, S) are **not** subtotaled; only the cost columns and column F are. The sum of `F` (contract prices) is meaningless but present in the original.
- Row 30 has a single grand total. There are **no per-supplier subtotals** in the July sheet.

### 3.6 Remarks block (rows 32-35)

| Cell | Content | Style |
|---|---|---|
| `B32` | `Inventory Remarks:` | Calibri 8 bold, red `FF0000`, no border |
| `N32` | `=TODAY()` | Calibri 10, format `m/d/yyyy;@` (cached value in the file: 2026-08-02) |
| `B33:F33` merged | remark 1 | red, wrap, row height 24 |
| `B34:F34` merged | remark 2 | red, wrap, row height 24 |
| `B35:F35` merged | remark 3 | red, wrap, row height 23.25 |
| `L34` | `Prepared by:` | Calibri 10, left-aligned |
| `Q34` | `Received by:` | Calibri 10, right-aligned |

Remarks are numbered by hand (`1. ...`, `2. ...`, `3. ...`). In the app they should come from write-off transactions and batch status, not free text. The signature labels are text only, with no lines and no names.

---

## 4. Formulas (per item row `r`)

| Cell | Formula |
|---|---|
| `H{r}` | `=F{r}*G{r}` |
| `J{r}` | `=F{r}*I{r}` |
| `L{r}` | `=F{r}*K{r}` |
| `N{r}` | `=F{r}*M{r}` |
| `P{r}` | original: `=J{r}*O{r}` (see section 9) |
| `R{r}` | `=F{r}*Q{r}` |
| `S{r}` | `=G{r}+I{r}+K{r}-M{r}-Q{r}` (original) |
| `T{r}` | `=F{r}*S{r}` |

Inputs (typed values) are `A`-`G`, `I`, `K`, `M`, `O`, `Q`. Supplier bands and the header carry no formulas.

---

## 5. Style tokens

| Token | Value |
|---|---|
| Title fill | `CCC0DA` (theme accent4, tint 0.6) |
| Band fill (first two Zuellig bands) | `B7DEE8` (accent5, tint 0.6) |
| Band fill (other suppliers) | `92CDDC` (accent5, tint 0.4) |
| Subtotal fill | `FFFFCC` |
| Remark font colour | `FF0000` |
| Body fill | white |
| Body font | Calibri 6 |
| Title font | Calibri 10 bold |
| Borders | thin, all sides, on header/body/subtotal; medium on the left edge of `A4`, `A5`, `A30` and top/bottom of `A30` |

The theme-tinted colours above are the standard Office 2007 palette values; if the generator can reference theme colours directly, prefer that.

---

## 6. Data mapping from the app

| Column(s) | Source in the app |
|---|---|
| A, B, C | Item code, description, unit |
| D, E | Batch number, expiration date |
| F | Contract price in effect for the period |
| G (qty) | Prior period's closing quantity |
| I | Sum of `received` transactions in the period |
| K | Sum of `return_from_ward` |
| M | Sum of `return_to_supplier` |
| O | Sum of `transfer_to_pharmacy` |
| Q | Sum of `consumption` (plus write-offs, if the department books them here) |
| S | Derived: beginning + received + ward returns - supplier returns - transfers - consumption |
| H, J, L, N, P, R, T | qty x price |

One row per (supplier item, batch). The same item can appear under two suppliers (Peptamen, `CONMED0141`, appears under DSPI at row 19 and RBC-MDC at row 23), so the row key is supplier + item + batch, not item code alone.

---

## 7. Layout generation algorithm

1. Create sheet `<MON>`. Set landscape, Folio, margins, column widths.
2. Write and merge rows 1-3 with the title text.
3. Write rows 4-5 headers and merges. Put the first supplier band in `B5`.
4. For each supplier group in the fixed supplier order: (a) write the band row (except the first group, whose band is `B5`), (b) write item rows.
5. Write the subtotal row directly after the last item row, with `SUM` over the full item range.
6. Skip one row, then write `Inventory Remarks:`, `=TODAY()`, the merged remark rows, and the signature labels.
7. Apply borders, fills, fonts and number formats to every cell in `A:T` for rows 4 through the subtotal row, including blank cells in band rows.

The original has fixed positions (subtotal at row 30, remarks at 32-35) because it has exactly 24 rows of body. The generator must compute these positions from the row count, and the subtotal range must follow (`6:N` where N is the last body row).

---

## 8. July data as a test fixture

Values are the cached values in the file. Use them to verify the generator reproduces the sheet.

| Row | Code | Desc (col B) | Unit | Batch | Exp | Price | G Beg | I Recv | K RetWard | M RetSup | O Xfer | Q Cons |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 6 | CONMED0367 | Standard or Polymeric Formulas, Ensure Gold, 1.6kg | can | 1263542 | 2027-11-08 | 2122.9 | 627 | 0 | 0 | 0 | 0 | 342 |
| 7 | **BAND** | ZUELLIG PHARMA CORPORATION | | | | | | | | | | |
| 8 | CONMED0370 | Standard or Polymeric Formulas, Pediasure Plus, 370g | sach | 1254535 | 2027-08-05 | 456.32 | 995 | 0 | 0 | 0 | 0 | 245 |
| 9 | CONMED0196 | Alitraq, 76g | sach | 85433QU | 2028-01-09 | 212.86 | 0 | 300 | 0 | 0 | 0 | 112 |
| 10 | CONMED0269 | Cancer Specific Formula, Prosure, 380g | can | 82440QU03 | 2027-10-09 | 900 | 9 | 29 | 0 | 0 | 0 | 0 |
| 11 | CONMED0219 | Renal Disease, Nepro HP, 237mL | can | 87402RA20 | 2027-07-01 | 155 | 336 | 3585 | 74 | 0 | 0 | 346 |
| 12 | CONMED0217 | Renal Disease, Nepro LP, 237mL | can | 85686RA20 | 2027-05-01 | 155 | 614 | 0 | 0 | 0 | 0 | 614 |
| 13 | CONMED0306 | Oxepa, Specialized Liquid Nutrition, 500mL | bot | 80811NR | 2026-11-29 | 803.26 | 50 | 50 | 0 | 0 | 0 | 29 |
| 14 | CONMED0243 | Standard or Polymeric Formulas, Ensure Gold, 850g | can | 1201126 | 2025-04-10 | 1099.85 | 2 | 0 | 0 | 0 | 0 | 2 |
| 15 | **BAND** | DISTRIBUTION SOLUTION PHILIPPINES INC. | | | | | | | | | | |
| 16 | CONMED0249 | Fiber-containing Formulas, Boost Optimum, 400g | can | (blank) | (none) | 908.18 | 14 | 0 | 0 | 0 | 0 | 0 |
| 17 | CONMED0145 | Fiber-containing Formulas, Boost Optimum, 800g | can | 52049510B1 | 2028-04-23 | 1768.18 | 0 | 0 | 0 | 0 | 0 | 0 |
| 18 | CONMED0153 | Formulas-containing Immune Enhancing,  Oral Impact | sach | 4059428220 | 2025-05-23 | 345.45 | 128 | 0 | 0 | 0 | 0 | 0 |
| 19 | CONMED0141 | Elemental or Semi-Elemental Formulas, Peptamen | can | 12538452 | 2027-06-27 | 1340.92 | 0 | 0 | 0 | 0 | 0 | 0 |
| 20 | CONMED0143 | Modular Components, BENEPROTEIN | can | 4352T33521 | 2026-12-17 | 1063.64 | 110 | 0 | 0 | 0 | 0 | 23 |
| 21 | **BAND** | RBC-MDC CORPORATION | | | | | | | | | | |
| 22 | CONMED0139 | Standard or Polymeric Formulas, Nutren Junior | can | 21769510B1 | 2024-06-25 | 700 | 115 | 0 | 0 | 0 | 0 | 0 |
| 23 | CONMED0141 | Elemental or Semi-Elemental Formulas, Peptamen | can | 22290017A4 | 2024-08-17 | 1207.5 | 12 | 0 | 0 | 0 | 0 | 0 |
| 24 | **BAND** | IVAXX MARKETING CORPORATION | | | | | | | | | | |
| 25 | CONMED0374 | Diabetes Specific Formula, Diben | can | 29WE0828 | 2026-12-05 | 1345 | 48 | 150 | 0 | 0 | 0 | 94 |
| 26 | CONMED0373 | Cancer Specific, Oral Nutrition Supplement, Supportan | bot | 29XA0073 | 2027-04-15 | 287 | 0 | 1516 | 0 | 0 | 500 | 552 |
| 27 | **BAND** | VAXIMAXX MARKETING VENTURES, INC. | | | | | | | | | | |
| 28 | CONMED0377 | Standard Polymeric Formula, Hinex Blendera | can | 5J802 | 10/0/2027 | 725 | 807 | 0 | 0 | 0 | 0 | 42 |
| 29 | CONMED0378 | Cancer Specific Formula, Neo-Mune | can | 5G901 | 7/0/2028 | 1050 | 134 | 0 | 0 | 0 | 0 | 91 |

Expected subtotal row (row 30, cached values): F = 16,646.06; H = 3,042,066.92; J = 1,322,638.00; L = 11,470.00; N = 0; P = 217,546,000.00 (bad, see below); R = 1,471,282.48; T = 2,749,922.44.

---

## 9. Quirks, errors and recommended handling

| # | Finding | Reproduce or fix? |
|---|---|---|
| 1 | **`P` (transfer to pharmacy cost) uses `=J*O` in every row.** It multiplies received *cost* by transfer qty instead of price by qty. Only row 26 has a non-zero `O` (500), so it produces 217,546,000 instead of 143,500 (`287 x 500`). | **Fix:** use `=F*O`. |
| 2 | **`S` subtracts `O` (transfers) only in row 26.** Every other row omits `-O`, so transfers would not reduce their balance if they were ever non-zero. | **Fix:** use one formula for all rows, `=G+I+K-M-O-Q`. |
| 3 | **`S11` and `S12` (Nepro HP/LP) omit `+K` (ward returns).** Nepro HP has K = 74, so its ending balance is 3,575 when it should be 3,649. | **Fix:** include `+K`. |
| 4 | `J8` (Pediasure received cost) has no formula. It is blank while `I8` = 0, so the total is unaffected today. | **Fix:** write the formula. |
| 5 | `E16` (Boost Optimum 400g expiry) is empty and `D16` is a single space. | Render blank; do not write a space. |
| 6 | `E28` = `'10/0/2027'` and `E29` = `'7/0/2028'` are **text** with a zero day, not dates. | Write real dates (or month/year text) from the batch record. |
| 7 | Column D mixes numbers (`1263542`) and text (`'85433QU'`). | Write as text, but note it changes the left/centre display slightly. |
| 8 | `F30` sums contract prices. | Keep for visual fidelity, or leave blank; pick one and document it. |
| 9 | Quantities are not subtotaled and there are no per-supplier subtotals. | Reproduce as-is (a per-supplier subtotal would be a layout change). |
| 10 | `N32` uses `=TODAY()`, which changes every time the file opens. | For an archived report, write the generation date as a static value. |

If the goal is an exact visual match, keep fixes 1-4 as an opt-in flag and default to the corrected formulas, because the original values are wrong wherever the bugs are active (row 26 transfer cost, row 11 ending balance).

---

## 10. Acceptance checklist

- [ ] Sheet is landscape, Folio, with the four margins above
- [ ] Column widths match section 2
- [ ] Merges match exactly (rows 1-3 across `A:T`, header merges, remarks `B:F`)
- [ ] All body text is Calibri 6; title rows Calibri 10 bold
- [ ] Fills: title `CCC0DA`, bands `B7DEE8` / `92CDDC`, subtotal `FFFFCC`
- [ ] Number formats per column match section 2
- [ ] Derived columns are live formulas, not pasted values
- [ ] Subtotal `SUM` range covers the whole body
- [ ] Remarks block and `Prepared by:` / `Received by:` labels present
- [ ] July fixture reproduces the cached quantities and totals (with fixes 1-4 the expected P, S11 and subtotal values change, so compare against the corrected numbers)
