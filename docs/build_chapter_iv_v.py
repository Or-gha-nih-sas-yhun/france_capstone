#!/usr/bin/env python3
"""
Append Chapter IV (Presentation, Analysis, and Interpretation of Results) and
Chapter V (Summary of Findings, Conclusions, and Recommendations) to the
Chapter III document of Mera's General Merchandise Store Management System.

The source file is never modified; the result is written to a new .docx.

IMPORTANT - SAMPLE DATA
-----------------------
No survey has been collected yet, so every respondent count, mean score and
verbal interpretation below is SAMPLE data. The generated document highlights
all of them in yellow and carries a note box at the start of Chapter IV.
When the real questionnaires are tallied:
  1. Replace the target means in the DATA section (or give exact means).
  2. Re-run this script:  python docs/build_chapter_iv_v.py
The tables, the totals, the verbal interpretations and the narrative figures
are all recomputed from the DATA section. Hand-written wording (descriptions of
screens, conclusions, recommendations) should still be reviewed.
"""
import re
import struct
import sys
import zipfile
import zlib
from decimal import Decimal, ROUND_HALF_UP
from pathlib import Path
from xml.sax.saxutils import escape

sys.path.insert(0, str(Path(__file__).resolve().parent))
from generate_chapter3_flowcharts import (CAPTIONS, FIRST_FIGURE, KEYS,  # noqa: E402
                                          OUT as FC_DIR, filename as fc_file)
from generate_chapter3_figures import FIGURES, OUT as FIG_DIR  # noqa: E402

DOCS = Path(__file__).resolve().parent
SRC = DOCS / "Chapter-III (2).docx"
OUT = DOCS / "Chapter-III-IV-V-complete.docx"

# --------------------------------------------------------------------------
# DATA  (SAMPLE VALUES - replace with the computed results of the real survey)
# --------------------------------------------------------------------------

# Likert interpretation, exactly as in Chapter III, Table 11.
SCALE = [
    (Decimal("4.21"), "Strongly Agree (Excellent)"),
    (Decimal("3.41"), "Agree (Very Good)"),
    (Decimal("2.61"), "Neutral (Good)"),
    (Decimal("1.81"), "Disagree (Fair)"),
    (Decimal("1.00"), "Strongly Disagree (Poor)"),
]

# Respondent groups, exactly as in Chapter III, Table 9.
GROUPS = [
    ("Store owner and merchants", 2),
    ("Store employees (cashiers and staff)", 5),
    ("Customers", 30),
    ("IT experts", 5),
]
_G = dict(GROUPS)
OWNER, STAFF = _G["Store owner and merchants"], _G["Store employees (cashiers and staff)"]
CUSTS, ITX = _G["Customers"], _G["IT experts"]
N_ALL = sum(n for _, n in GROUPS)          # 42 with the sample counts
N_ADMIN = OWNER + STAFF + ITX              # owner/merchants + employees + IT experts
N_CUST = CUSTS + ITX                       # customers + IT experts
N_USE = OWNER + STAFF + CUSTS              # usability raters
N_IT = ITX
SHORT = {"Store owner and merchants": "store owner and merchants",
         "Store employees (cashiers and staff)": "store employees",
         "Customers": "customers", "IT experts": "IT experts"}

ADMIN_WHO = (f"the {N_ADMIN} respondents who used the administrator portal "
             "(the store owner and merchants, the store employees, and the IT experts)")
CUST_WHO = (f"the {N_CUST} respondents who used the customer website and the mobile "
            "application (the customers and the IT experts)")

# (phrase used after "How functional is our system in terms of ...", sample target mean)
DASHBOARD = [
    ("displaying the Daily Sales", 4.58),
    ("displaying the Weekly Sales", 4.50),
    ("displaying the Monthly Sales", 4.50),
    ("displaying the Total Revenue", 4.67),
    ("displaying the Total Number of Products", 4.58),
    ("displaying the Low Stock Count", 4.42),
    ("displaying the Recent Sales", 4.50),
    ("displaying the Low Stock Alerts", 4.75),
    ("displaying the Seven-Day Sales Trend chart", 4.33),
    ("displaying the Top-Selling Products and Stock by Category charts", 4.42),
]
ADMIN_FN = [
    ("managing product records and inventory", 4.67),
    ("importing products from a CSV file", 4.33),
    ("setting bulk prices and units of measure for products", 4.42),
    ("processing POS sales through the cart and checkout", 4.75),
    ("accepting cash and GCash payments and computing the change", 4.58),
    ("generating and printing receipts", 4.67),
    ("deducting sold quantities from the inventory automatically", 4.75),
    ("generating weekly, monthly, and yearly sales reports and exporting them to CSV", 4.58),
    ("viewing top-selling products and recent transactions", 4.50),
    ("managing customer inquiries and sending email responses", 4.50),
    ("replying to customers through the support chat", 4.42),
    ("managing user accounts and roles", 4.50),
    ("monitoring activity logs", 4.42),
    ("managing system settings and security", 4.50),
]
CUSTOMER = [
    ("displaying the store landing page and product catalogue", 4.60),
    ("searching and filtering products by name, SKU, or category", 4.54),
    ("displaying product prices and stock status", 4.57),
    ("switching between the grid view and the table view of products", 4.40),
    ("submitting store inquiries", 4.60),
    ("registering and logging in, including Google sign-in", 4.46),
    ("recovering a forgotten password through an email reset link", 4.49),
    ("viewing the customer account and inquiry reply notifications", 4.34),
    ("providing chatbot assistance on store hours, location, products, and payment", 4.54),
]
MOBILE = [
    ("downloading and installing the Android application from the store website", 4.51),
    ("browsing and searching products on a mobile device", 4.60),
    ("submitting inquiries through the mobile application", 4.54),
    ("receiving push notifications when the store replies to an inquiry", 4.43),
    ("using the chatbot on the mobile application", 4.49),
]
# (characteristic, number of questionnaire items, sample target mean)   - Chapter III, Table 3 / 10
ISO = [
    ("Functionality", 10, 4.50),
    ("Usability", 6, 4.37),
    ("Reliability", 5, 4.28),
    ("Efficiency", 5, 4.08),
    ("Security", 5, 4.36),
    ("Maintainability", 4, 4.15),
]
# (criterion, number of questionnaire items, sample target mean)       - Chapter III, Table 10
USE = [
    ("Usefulness", 8, 4.35),
    ("Satisfaction", 7, 4.28),
    ("Ease of Use and Learning", 15, 4.22),
]

# --------------------------------------------------------------------------
# Statistics helpers
# --------------------------------------------------------------------------

def r2(x):
    return Decimal(str(x)).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def mean_from_target(target, n):
    """Mean achievable with n whole-number ratings, closest to the target."""
    total = round(float(target) * n)
    return r2(Decimal(total) / Decimal(n))


def label(m):
    m = r2(m)
    for lo, text in SCALE:
        if m >= lo:
            return text
    return SCALE[-1][1]


def avg(values):
    return r2(sum(Decimal(v) for v in values) / Decimal(len(values)))


def pct(f, n):
    return (Decimal(f) * 100 / Decimal(n)).quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def build_functional(rows, n):
    items = [dict(text=t, mean=mean_from_target(m, n)) for t, m in rows]
    return items, avg([i["mean"] for i in items])


dash_items, dash_total = build_functional(DASHBOARD, N_ADMIN)
admin_items, admin_total = build_functional(ADMIN_FN, N_ADMIN)
cust_items, cust_total = build_functional(CUSTOMER, N_CUST)
mob_items, mob_total = build_functional(MOBILE, N_CUST)

iso_items = [dict(text=c, mean=mean_from_target(m, k * N_IT)) for c, k, m in ISO]
iso_total = avg([i["mean"] for i in iso_items])
use_items = [dict(text=c, mean=mean_from_target(m, k * N_USE)) for c, k, m in USE]
use_total = avg([i["mean"] for i in use_items])

summary_rows = [
    ("Administrator dashboard (functionality)", N_ADMIN, dash_total),
    ("Administrator management functions (functionality)", N_ADMIN, admin_total),
    ("Customer website (functionality)", N_CUST, cust_total),
    ("Android mobile application (functionality)", N_CUST, mob_total),
    ("Software quality based on ISO/IEC 25010", N_IT, iso_total),
    ("Usability (usefulness, satisfaction, ease of use and learning)", N_USE, use_total),
]
overall = avg([m for _, _, m in summary_rows])

# --------------------------------------------------------------------------
# WordprocessingML helpers (match the formatting of Chapter III)
# --------------------------------------------------------------------------
FONT = ('<w:rFonts w:ascii="Courier New" w:cs="Courier New" '
        'w:eastAsia="Courier New" w:hAnsi="Courier New"/>')
TABLE_W = 8306  # text width: A4 11906 - left 2160 - right 1440


def rpr(b=False, i=False, sz=None, hl=False):
    s = FONT
    if b:
        s += "<w:b/><w:bCs/>"
    if i:
        s += "<w:i/><w:iCs/>"
    if sz:
        s += f'<w:sz w:val="{sz}"/><w:szCs w:val="{sz}"/>'
    if hl:
        s += '<w:highlight w:val="yellow"/>'
    return f"<w:rPr>{s}</w:rPr>"


def run(text, **kw):
    return f'<w:r>{rpr(**kw)}<w:t xml:space="preserve">{escape(text)}</w:t></w:r>'


def runs(text, b=False, i=False, sz=None):
    """Text with ⟦sample values⟧ marked; those are emitted highlighted."""
    out = ""
    for part in re.split(r"(⟦.*?⟧)", text):
        if not part:
            continue
        if part.startswith("⟦"):
            out += run(part[1:-1], b=b, i=i, sz=sz, hl=True)
        else:
            out += run(part, b=b, i=i, sz=sz)
    return out


def para(content, align="both", first=720, left=None, hanging=None, line=480,
         before=None, after=None, keep_next=False, page_break=False, style="style0"):
    ppr = f'<w:pStyle w:val="{style}"/>'
    if keep_next:
        ppr += "<w:keepNext/>"
    if page_break:
        ppr += "<w:pageBreakBefore/>"
    sp = ""
    if before is not None:
        sp += f' w:before="{before}"'
    if after is not None:
        sp += f' w:after="{after}"'
    ppr += f'<w:spacing{sp} w:lineRule="auto" w:line="{line}"/>'
    if left is not None:
        ppr += f'<w:ind w:left="{left}" w:hanging="{hanging or 0}"/>'
    elif first:
        ppr += f'<w:ind w:firstLine="{first}"/>'
    ppr += f'<w:jc w:val="{align}"/><w:rPr>{FONT}</w:rPr>'
    return f"<w:p><w:pPr>{ppr}</w:pPr>{content}</w:p>"


def body(text):
    return para(runs(text))


def chapter_title(text, page_break=False):
    return para(runs(text, b=True), align="center", first=0, page_break=page_break, keep_next=True)


def heading(text, level=2):
    return (f'<w:p><w:pPr><w:pStyle w:val="style{level}"/><w:keepNext/>'
            f'<w:spacing w:lineRule="auto" w:line="480"/><w:rPr>{FONT}</w:rPr></w:pPr>'
            f'<w:r><w:rPr>{FONT}</w:rPr><w:t xml:space="preserve">{escape(text)}</w:t></w:r></w:p>')


def table_caption(text):
    return para(runs(text, b=True), align="center", first=0, line=276, before=200, after=120, keep_next=True)


def figure_caption(text):
    return para(runs(text, b=True), align="center", first=0, line=276, before=120, after=240)


def blank():
    return '<w:p><w:pPr><w:pStyle w:val="style0"/></w:pPr></w:p>'


def cell_para(text, align="left", b=False, i=False, sz=20, keep_next=False):
    kn = "<w:keepNext/>" if keep_next else ""
    return (f'<w:p><w:pPr><w:pStyle w:val="style0"/>{kn}<w:spacing w:after="40" w:lineRule="auto" w:line="251"/>'
            f'<w:jc w:val="{align}"/><w:rPr>{FONT}</w:rPr></w:pPr>{runs(text, b=b, i=i, sz=sz)}</w:p>')


def cell(width, paras, fill=None, border="single", bsz=4, bcolor="000000", valign="center"):
    b = "".join(f'<w:{s} w:val="{border}" w:sz="{bsz}" w:space="0" w:color="{bcolor}"/>'
                for s in ("top", "left", "bottom", "right"))
    shd = f'<w:shd w:val="clear" w:color="auto" w:fill="{fill}"/>' if fill else ""
    return (f'<w:tc><w:tcPr><w:tcW w:w="{width}" w:type="dxa"/><w:tcBorders>{b}</w:tcBorders>{shd}'
            '<w:tcMar><w:top w:w="60" w:type="dxa"/><w:left w:w="100" w:type="dxa"/>'
            '<w:bottom w:w="60" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tcMar>'
            f'<w:vAlign w:val="{valign}"/></w:tcPr>{paras}</w:tc>')


def table(widths, rows_xml):
    assert sum(widths) == TABLE_W, sum(widths)
    borders = "".join(f'<w:{s} w:val="single" w:sz="4" w:space="0" w:color="auto"/>'
                      for s in ("top", "left", "bottom", "right", "insideH", "insideV"))
    grid = "".join(f'<w:gridCol w:w="{w}"/>' for w in widths)
    return (f'<w:tbl><w:tblPr><w:tblW w:w="{TABLE_W}" w:type="dxa"/><w:tblBorders>{borders}</w:tblBorders>'
            '<w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="0" w:type="dxa"/>'
            '<w:bottom w:w="0" w:type="dxa"/></w:tblCellMar>'
            '<w:tblLook w:val="0000" w:firstRow="0" w:lastRow="0" w:firstColumn="0" w:lastColumn="0" '
            f'w:noHBand="0" w:noVBand="0"/></w:tblPr><w:tblGrid>{grid}</w:tblGrid>{"".join(rows_xml)}</w:tbl>')


def row(widths, texts, aligns, header=False, total=False, fills=None, keep=True):
    trpr = "<w:cantSplit/>" + ("<w:tblHeader/>" if header else "")
    fill = "D9D9D9" if header else ("F2F2F2" if total else None)
    cells = ""
    for w, t, a in zip(widths, texts, aligns):
        # keep the rows of a table together on one page (the total row ends the chain)
        cells += cell(w, cell_para(t, align=a, b=header or total, keep_next=keep and not total), fill=fill)
    return f"<w:tr><w:trPr>{trpr}</w:trPr>{cells}</w:tr>"


def result_table(items, total, first_col="CRITERIA", stem=None):
    w = [4306, 1100, 2900]
    rows = [row(w, [first_col, "MEAN", "VERBAL INTERPRETATION"], ["center"] * 3, header=True)]
    for i, it in enumerate(items):
        text = stem.format(it["text"]) if stem else it["text"]
        # short tables stay on one page; long ones may break, but the last item always stays with the total
        keep = len(items) <= 8 or i >= len(items) - 1
        rows.append(row(w, [text, f"⟦{it['mean']}⟧", f"⟦{label(it['mean'])}⟧"], ["left", "center", "center"],
                        keep=keep))
    rows.append(row(w, ["TOTAL WEIGHTED MEAN", f"⟦{total}⟧", f"⟦{label(total)}⟧"], ["left", "center", "center"], total=True))
    return table(w, rows)


def figure_box(hint):
    c = cell(TABLE_W,
             cell_para("⟦[ INSERT SCREENSHOT HERE ]⟧", align="center", b=True, sz=22, keep_next=True)
             + cell_para(hint, align="center", i=True, sz=18, keep_next=True),
             fill="F2F2F2", border="dashed", bsz=8, bcolor="7F7F7F")
    rw = '<w:tr><w:trPr><w:cantSplit/><w:trHeight w:val="3600" w:hRule="atLeast"/></w:trPr>' + c + "</w:tr>"
    return table([TABLE_W], [rw])


def note_box(lead, text):
    c = cell(TABLE_W,
             cell_para(f"⟦{lead}⟧ {text}", align="both", sz=18),
             fill="FFF2CC", border="single", bsz=8, bcolor="BF9000", valign="top")
    return table([TABLE_W], [f'<w:tr><w:trPr><w:cantSplit/></w:trPr>{c}</w:tr>'])


ERD_NO = FIRST_FIGURE + len(KEYS)          # the ERD follows the flowcharts in Chapter III
_fig_counter = [ERD_NO]                    # Chapter IV figures continue after the ERD


def figure(caption, hint, description):
    _fig_counter[0] += 1
    no = _fig_counter[0]
    return (figure_box(hint) + figure_caption(f"Figure {no}. {caption}")
            + body(description.replace("{n}", str(no))))


# --------------------------------------------------------------------------
# Narrative helpers
# --------------------------------------------------------------------------

def join_and(parts):
    parts = list(parts)
    if len(parts) <= 1:
        return "".join(parts)
    if len(parts) == 2:
        return f"{parts[0]} and {parts[1]}"
    return ", ".join(parts[:-1]) + f", and {parts[-1]}"


def sentence_start(text):
    """Uppercase only the first character, preserving acronyms such as POS."""
    return text[:1].upper() + text[1:]


def extremes(items):
    hi = max(i["mean"] for i in items)
    lo = min(i["mean"] for i in items)
    return ([i["text"] for i in items if i["mean"] == hi], hi,
            [i["text"] for i in items if i["mean"] == lo], lo)


def narrate(no, items, total, closing, who):
    means = [i["mean"] for i in items]
    lo, hi = min(means), max(means)
    labs = {label(m) for m in means}
    if len(labs) == 1:
        first = (f"Table {no} shows that the system was rated ⟦{label(means[0])}⟧ across all criteria by {who}, "
                 f"with mean scores ranging from ⟦{lo}⟧ to ⟦{hi}⟧.")
    else:
        first = (f"Table {no} shows that the system was rated ⟦{label(total)}⟧ overall by {who}, "
                 f"with mean scores ranging from ⟦{lo}⟧ to ⟦{hi}⟧.")
    spec = join_and([f"⟦{i['mean']}⟧ for {i['text']}" for i in items])
    text = f"{first} Specifically, the system obtained {spec}."
    odd = [i for i in items if label(i["mean"]) != label(total)]
    if odd and len(labs) > 1:
        text += (" The criteria interpreted as ⟦" + label(odd[0]["mean"]) + "⟧ were "
                 + join_and([f"{i['text']} (⟦{i['mean']}⟧)" for i in odd]) + ".")
    text += (f" With a total weighted mean of ⟦{total}⟧, the overall interpretation is ⟦{label(total)}⟧, "
             f"which indicates that {closing}")
    return text


# --------------------------------------------------------------------------
# CHAPTER IV
# --------------------------------------------------------------------------

def chapter_iv():
    x = []
    x.append(chapter_title("CHAPTER IV", page_break=True))
    x.append(chapter_title("PRESENTATION, ANALYSIS, AND INTERPRETATION OF RESULTS"))
    x.append(note_box(
        "NOTE TO THE RESEARCHERS (delete before submission):",
        "No survey results have been collected yet. Every value highlighted in yellow in Chapters IV and V "
        "(respondent counts, mean scores, verbal interpretations and the findings that cite them) is SAMPLE DATA "
        "that only shows the format. Replace each value with the weighted means computed from the actual "
        "questionnaires and revise the narrative to match. Each \"INSERT SCREENSHOT\" box marks where a "
        "screenshot of the running system belongs."))
    x.append(blank())
    x.append(body(
        "After the system was developed and tested, it was evaluated by the respondents described in Chapter III. "
        "This chapter presents the results of the evaluation in the following order: the profile of the "
        "respondents; the functionality of the administrator dashboard, the administrator management functions, "
        "the customer website, and the Android mobile application; the software quality of the system based on "
        "ISO/IEC 25010; and the usability of the system. The responses were tallied, the weighted mean of each "
        "criterion was computed, and the results were interpreted using the five-point scale in Table 11. "
        "Criteria that a respondent did not use were marked N/A and were excluded from the computation."))

    # ---- Profile of respondents -------------------------------------------
    x.append(heading("Profile of the Respondents"))
    x.append(body(f"Table 12 shows the distribution of the {N_ALL} respondents who evaluated the system."))
    x.append(table_caption("Table 12. Profile of the Respondents"))
    # Give the final column enough room to avoid splitting words such as
    # "Usability" and "Evaluation" across lines in Word.
    w = [3206, 1500, 1800, 1800]
    rows = [row(w, ["RESPONDENT GROUP", "FREQUENCY", "PERCENTAGE", "BASIS OF EVALUATION"], ["center"] * 4, header=True)]
    basis = {"IT experts": "ISO/IEC 25010"}
    for g, n in GROUPS:
        rows.append(row(w, [g, f"⟦{n}⟧", f"⟦{pct(n, N_ALL)}%⟧", basis.get(g, "Usability")],
                        ["left", "center", "center", "center"]))
    rows.append(row(w, ["TOTAL", f"⟦{N_ALL}⟧", "⟦100.00%⟧", ""], ["left", "center", "center", "center"], total=True))
    x.append(table(w, rows))
    x.append(blank())
    ordered = sorted(GROUPS, key=lambda g: -g[1])
    rest = join_and([f"the {SHORT[g]} with ⟦{n}⟧ respondents (⟦{pct(n, N_ALL)}%⟧)" for g, n in ordered[1:]])
    x.append(body(
        f"Table 12 shows that the {SHORT[ordered[0][0]]} formed the largest group with ⟦{ordered[0][1]}⟧ "
        f"respondents (⟦{pct(ordered[0][1], N_ALL)}%⟧), followed by {rest}. "
        + ("The large share of customers means that the system was evaluated mainly from the "
           "point of view of its intended end users, while the store owner, the store employees, and the IT "
           "experts provided the viewpoints of those who operate the system and who assess its technical "
           "quality." if ordered[0][0] == "Customers" else
           "The respondents therefore represent both the people who operate the system and those who use it.")))

    # ---- Functionality ----------------------------------------------------
    x.append(heading("Functionality of the System"))
    x.append(body(
        "The functionality of the system was evaluated according to the interface of each type of user. The "
        f"administrator portal was evaluated by {ADMIN_WHO}, while the customer website and the Android mobile "
        f"application were evaluated by {CUST_WHO}."))

    # Dashboard
    x.append(heading("Administrator Dashboard", 3))
    x.append(body(f"Table 13 presents the functionality of the administrator dashboard as rated by {ADMIN_WHO}."))
    x.append(table_caption("Table 13. Functionality of the Administrator Dashboard"))
    x.append(result_table(dash_items, dash_total, stem="How functional is our system in terms of {}?"))
    x.append(blank())
    x.append(body(narrate(
        13, dash_items, dash_total,
        "the dashboard presents the sales, revenue, product, and low-stock information of the store clearly and "
        "accurately, giving the store owner a quick view of the daily operation of the business.", "the respondents")))
    x.append(figure("Administrator Dashboard",
                    "Capture: Dashboard Overview page  (/dashboard, logged in as administrator)",
                    "Figure {n} shows the dashboard of the administrator portal. It displays the Daily Sales, Weekly "
                    "Sales, Monthly Sales, Total Revenue, the number of Products, and the Low Stock count, together "
                    "with charts for the seven-day sales trend, the top-selling products, and the stock by category. "
                    "The Recent Sales table lists the sale ID, cashier, timestamp, and total amount, the Low Stock "
                    "Alerts list shows the products that need to be restocked, and the Quick Actions give shortcuts "
                    "to POS Checkout, Inventory, Support Chat, and Sales Reports. This section helps the "
                    "administrator monitor the sales and the stock of the store at a glance."))

    # Admin management functions
    x.append(heading("Administrator Management Functions", 3))
    x.append(body(
        f"Table 14 presents the functionality of the management functions of the administrator portal as rated by "
        f"{ADMIN_WHO}."))
    x.append(table_caption("Table 14. Functionality of the Administrator Management Functions"))
    x.append(result_table(admin_items, admin_total, stem="How functional is our system in terms of {}?"))
    x.append(blank())
    x.append(body(narrate(
        14, admin_items, admin_total,
        "the administrator portal effectively supports the whole workflow of the store, from the management of "
        "products and inventory and the processing of sales, to the generation of reports, the handling of "
        "customer inquiries, and the control of user accounts, activity logs, and system settings.",
        "the respondents")))
    x.append(figure("Inventory Management",
                    "Capture: Inventory Management page, Product List  (/products)",
                    "Figure {n} shows the part of the system where the administrator manages the Product List. Each "
                    "product is displayed with its name, category, stock quantity, and price, together with the "
                    "actions for editing or deleting the product, and a search box allows a product to be located "
                    "quickly. This section helps the administrator keep the product records, prices, and stock "
                    "quantities of the store accurate and up to date."))
    x.append(figure("Adding and Editing Products, Bulk Pricing, and CSV Import",
                    "Capture: Add Product form and Import Products from CSV window  (/products)",
                    "Figure {n} shows the form used to add or edit a product, with fields for the Product Name, SKU, "
                    "Price (PHP), Stock Qty, Unit (such as pieces, reams, packs, boxes, and kilograms), Bulk Price "
                    "(PHP), and the quantity at which the bulk price starts. A separate window, Import Products from "
                    "CSV, allows many products to be loaded at once. This section speeds up the encoding of the "
                    "inventory and supports both retail and wholesale pricing."))
    x.append(figure("Point of Sale: Product Catalog and Cart Summary",
                    "Capture: Point of Sale (POS) page with items in the cart  (/pos)",
                    "Figure {n} shows the Point of Sale screen. The Product Catalog lists the products with their "
                    "category, stock, and price and can be searched, and a product is added to the Cart Summary with "
                    "one action. In the cart, the cashier can increase or decrease quantities, remove an item, or "
                    "clear the cart, while products without stock are marked Unavailable and the price changes to the "
                    "bulk price once the bulk quantity is reached. This section helps the cashier prepare a sale "
                    "quickly and with the correct total."))
    x.append(figure("Point of Sale: Cash and GCash Payment and Receipt",
                    "Capture: Cash Tendered field, GCash QR Code window, and Receipt Ready window  (/pos)",
                    "Figure {n} shows the payment part of the Point of Sale. For a cash payment, the cashier enters "
                    "the Cash Tendered (PHP) and completes the checkout, and the system computes the change. For a "
                    "GCash payment, the GCash QR Code is displayed and the cashier confirms the payment. After the "
                    "checkout, the Receipt Ready window shows the items, quantities, and totals with the option to "
                    "Print Receipt, and the sold quantities are deducted from the inventory automatically. This "
                    "section ensures that every sale is recorded, receipted, and reflected in the stock."))
    x.append(figure("Sales Reports",
                    "Capture: Sales Reports page, showing a period and the Print / CSV export options  (/reports)",
                    "Figure {n} shows the part of the system where the administrator generates sales reports. The "
                    "report presents the Total Revenue, the number of Transactions, and the Average Sale for the "
                    "selected period (weekly, monthly, yearly, or all records), the Top Selling Products with their "
                    "category, units sold, and revenue, and the Recent Transactions with the sale ID, cashier, date "
                    "and time, and total. The report can be printed or exported to a CSV file. This section helps "
                    "the store owner evaluate sales performance without computing the figures by hand."))
    x.append(figure("Customer Inquiries",
                    "Capture: Customer Inquiries page with Inquiry History  (/inquiries)",
                    "Figure {n} shows the part of the system where the administrator manages the inquiries sent by "
                    "customers. The inquiries can be searched and filtered by status (All inquiries, Pending, or "
                    "Responded), and the Inquiry History lists the subject and message of each inquiry. The "
                    "administrator writes a response and selects Save Response, which sends the reply to the "
                    "customer by email and by notification, and may also mark an inquiry as Responded or Pending "
                    "or delete it. This section ensures that customer questions are answered and documented."))
    x.append(figure("Customer Support Chat",
                    "Capture: Customer Support Chat page  (/chat)",
                    "Figure {n} shows the support chat workspace. The Customer Questions panel lists the latest "
                    "website inquiries with their pending or responded status and provides a reply template, while "
                    "the Team Conversation allows the store personnel to exchange messages. Frequently asked topics "
                    "such as Store Hours, Location, Products, and Payment, and quick replies such as Hello, "
                    "Available, Out of Stock, Hours, and Address, help the personnel answer common questions "
                    "faster. This section supports prompt and consistent communication with customers."))
    x.append(figure("User Accounts",
                    "Capture: User Accounts page and Create User Account window  (/users)",
                    "Figure {n} shows the part of the system where the administrator manages user accounts. The "
                    "page summarizes the Total Accounts, Administrators, and Customers, and a table lists each "
                    "user's profile and contact details, system role, registration date, and the actions to change "
                    "the role or delete the account. A form allows a new account to be created by entering the full "
                    "name, email address, password, and system access role. This section controls who can access "
                    "the system and what each user is allowed to do."))
    x.append(figure("System Activity Logs",
                    "Capture: System Activity Logs page  (/logs)",
                    "Figure {n} shows the part of the system where the administrator monitors the activity logs. "
                    "The page shows the Total Activities and the number of Admin Actions, Customer Actions, and "
                    "Guest Actions, and a table lists the timestamp, user name, role, action type, activity "
                    "details, IP address, and browser of each activity, with a search option. This section supports "
                    "accountability by keeping an audit trail of the actions performed in the system."))
    x.append(figure("System Settings",
                    "Capture: System Settings page  (/settings)",
                    "Figure {n} shows the part of the system where the administrator manages the system settings. "
                    "It presents a System Overview of the Total Revenue, Sales Count, and Active Users, the Export "
                    "& Backup option for downloading the records, the Change Admin Password form, and the "
                    "Maintenance Actions for seeding demo products, resetting the sales history, and clearing the "
                    "chat history. This section helps the administrator protect the account, back up the data, and "
                    "maintain the system."))

    # Customer website
    x.append(heading("Customer Website", 3))
    x.append(body(f"Table 15 presents the functionality of the customer website as rated by {CUST_WHO}."))
    x.append(table_caption("Table 15. Functionality of the Customer Website"))
    x.append(result_table(cust_items, cust_total, stem="How functional is our system in terms of {}?"))
    x.append(blank())
    x.append(body(narrate(
        15, cust_items, cust_total,
        "customers can view the products and their availability, send inquiries, manage their accounts, and "
        "receive assistance from the chatbot without having to visit the store.", "the respondents")))
    x.append(figure("Store Landing Page and Product Catalogue",
                    "Capture: Home page, Our Products section with the category filter and Grid / DataTable view  (/)",
                    "Figure {n} shows the part of the system where customers browse the Our Products catalogue. The "
                    "products can be searched by name or SKU, filtered by category, and displayed in a Grid View or "
                    "a DataTable view. Each product shows its category, SKU, price, and stock status (In Stock, "
                    "Only a few left, or Out of Stock). This section lets customers check the products and "
                    "their availability before going to the store."))
    x.append(figure("Store Inquiry Form",
                    "Capture: Send Store Inquiry form and the Inquiry Received confirmation  (/#inquiry)",
                    "Figure {n} shows the Send Store Inquiry form, where a customer enters the full name, email "
                    "address, subject, and inquiry message to ask about bulk sales, availability, or pickup "
                    "schedules. A confirmation message appears once the inquiry is received, and the store location "
                    "at Stall No. 18 in the Bantayan Public Market is displayed beside the form. This section "
                    "gives customers a direct way to communicate with the store."))
    x.append(figure("Customer Registration and Login",
                    "Capture: Create Account page and Sign In page with the Sign in with Google option  (/register, /login)",
                    "Figure {n} shows the pages where customers create an account and log in. The Create Account "
                    "form asks for the full name, email address, password, and password confirmation, while the "
                    "Sign In page asks for the email address and password and also offers sign-in with a Google "
                    "account. This section allows customers to track their inquiries and receive notifications "
                    "through a secure account."))
    x.append(figure("Password Recovery",
                    "Capture: Reset Password page and Create New Password page  (/forgot-password, /reset-password)",
                    "Figure {n} shows the password recovery pages. The customer enters the registered email address "
                    "and selects Send Reset Link, and a secure link that expires after a limited time is sent to the "
                    "email. The link opens the Create New Password page, where the new password is entered and "
                    "confirmed. This section allows customers to recover their accounts without the help of the "
                    "administrator."))
    x.append(figure("Customer Account and Notifications",
                    "Capture: Customer Account page and Notifications page showing a reply from the store  (/profile, /notifications)",
                    "Figure {n} shows the Customer Account page and the Notifications page. When the administrator "
                    "replies to an inquiry, the alert Admin Replied to your Inquiry appears in the notifications of "
                    "the customer. This section keeps customers informed of the store's responses to their "
                    "concerns."))
    x.append(figure("Chatbot Assistance",
                    "Capture: Chat with Us window with the Store Hours, Location, Products, and Payment buttons",
                    "Figure {n} shows the Chat with Us window, where customers can use the quick buttons for Store "
                    "Hours, Location, Products, and Payment, or type a question about a product to see its price and "
                    "availability. This section gives customers immediate answers to common questions even when "
                    "the store personnel are not available."))

    # Mobile
    x.append(heading("Android Mobile Application", 3))
    x.append(body(
        f"Table 16 presents the functionality of the Android mobile application as rated by {CUST_WHO}."))
    x.append(table_caption("Table 16. Functionality of the Android Mobile Application"))
    x.append(result_table(mob_items, mob_total, stem="How functional is our system in terms of {}?"))
    x.append(blank())
    x.append(body(narrate(
        16, mob_items, mob_total,
        "the Android application gives customers convenient access to the catalogue of the store, to inquiries, "
        "and to notifications of the store's replies on their mobile devices.", "the respondents")))
    x.append(figure("Android Mobile Application",
                    "Capture: Mera's Store customer app on an Android device (home / catalogue, inquiry, notification)",
                    "Figure {n} shows the Mera's Store customer application running on an Android device. The "
                    "application can be downloaded from the Download App button of the store website and opens the "
                    "customer portal, where customers can browse and search the products, log in or register, send "
                    "inquiries, use the chatbot, and receive a push notification when the store replies. This "
                    "section highlights the portability of the system, allowing customers to use it anytime and "
                    "anywhere."))

    # ---- ISO 25010 --------------------------------------------------------
    x.append(heading("Software Quality Based on ISO/IEC 25010"))
    x.append(body(
        f"Table 17 presents the quality of the system as rated by the {N_IT} IT experts according to the "
        "characteristics of the ISO/IEC 25010 Systems and Software Quality Model."))
    x.append(table_caption("Table 17. Software Quality Based on ISO/IEC 25010"))
    x.append(result_table(iso_items, iso_total, first_col="CRITERIA"))
    x.append(blank())
    ranked = sorted(iso_items, key=lambda i: (-i["mean"], [c for c, _, _ in ISO].index(i["text"])))
    strong = [i for i in iso_items if label(i["mean"]) == SCALE[0][1]]
    lower = [i for i in iso_items if label(i["mean"]) != SCALE[0][1]]
    txt = (f"Table 17 shows that the system was rated ⟦{label(iso_total)}⟧ overall by the IT experts, with a total "
           f"weighted mean of ⟦{iso_total}⟧. Specifically, the system obtained "
           + join_and([f"⟦{i['mean']}⟧ for {i['text'].lower()}" for i in ranked]) + ". ")
    if lower:
        txt += (join_and([i["text"].lower() for i in lower]).capitalize() +
                f" {'was' if len(lower) == 1 else 'were'} rated ⟦{label(lower[0]['mean'])}⟧, while the "
                f"{'other characteristic was' if len(strong) == 1 else 'other characteristics were'} rated "
                f"⟦{SCALE[0][1]}⟧. ")
    txt += (f"These findings confirm that the system is highly suitable and secure for the operation of the "
            f"store, though there is room for improvement in "
            + join_and([i["text"].lower() for i in lower] or ["its lowest-rated characteristic"]) +
            " to further enhance the overall quality of the system.")
    x.append(body(txt))
    best = ranked[0]["text"]
    x.append(figure("Software Best Quality Function",
                    "Capture: POS checkout with items in the cart and the Receipt Ready window  (/pos)",
                    f"The figure above shows the best quality function of the system, which is {best.lower()}. "
                    "In the POS checkout, the administrator adds products to the cart, receives a cash or GCash "
                    "payment, and prints the receipt, while the sold quantities are deducted from the inventory "
                    "automatically and the sale is recorded for the reports."))

    # ---- Usability --------------------------------------------------------
    x.append(heading("Usability of the System"))
    x.append(body(
        f"Table 18 presents the usability of the system as rated by the {N_USE} respondents composed of the store "
        "owner and merchants, the store employees, and the customers, based on the usefulness, satisfaction, and "
        "ease of use and learning of the system."))
    x.append(table_caption("Table 18. Usability of the System"))
    x.append(result_table(use_items, use_total))
    x.append(blank())
    ranked_u = sorted(use_items, key=lambda i: -i["mean"])
    x.append(body(
        f"Table 18 shows that the system was rated ⟦{label(use_total)}⟧ overall, with mean scores ranging from "
        f"⟦{ranked_u[-1]['mean']}⟧ to ⟦{ranked_u[0]['mean']}⟧. Specifically, the system obtained "
        + join_and([f"⟦{i['mean']}⟧ for {i['text'].lower()}" for i in use_items]) +
        f". With a total weighted mean of ⟦{use_total}⟧, the findings confirm that the system provides a positive "
        "user experience, balancing usability, practicality, and satisfaction, thereby supporting effective use "
        "and adoption by the store owner, the store employees, and the customers."))
    x.append(figure("Software Usability",
                    "Capture: Store landing page showing the product catalogue, the Log In option, and the Download App button  (/)",
                    "The figure above shows the landing page of the system, where customers can view the products "
                    "of the store, send an inquiry, log in or register, and download the Android application. The "
                    "simple layout and the clear labels make the system easy to use and to learn for customers who "
                    "have limited technical knowledge."))

    # ---- Summary ----------------------------------------------------------
    x.append(heading("Summary of the Evaluation Results"))
    x.append(body("Table 19 summarizes the weighted mean of each component evaluated in this study."))
    x.append(table_caption("Table 19. Summary of the Evaluation Results"))
    # Keep the two compact numeric columns readable while allowing the verbal
    # interpretation to wrap naturally as a phrase.
    w = [3466, 1700, 1000, 2140]
    rows = [row(w, ["COMPONENT", "RESPONDENTS", "MEAN", "VERBAL INTERPRETATION"], ["center"] * 4, header=True)]
    for name, n, m in summary_rows:
        rows.append(row(w, [name, f"⟦{n}⟧", f"⟦{m}⟧", f"⟦{label(m)}⟧"], ["left", "center", "center", "center"]))
    rows.append(row(w, ["OVERALL WEIGHTED MEAN", "", f"⟦{overall}⟧", f"⟦{label(overall)}⟧"],
                    ["left", "center", "center", "center"], total=True))
    x.append(table(w, rows))
    x.append(blank())
    ms = [m for _, _, m in summary_rows]
    x.append(body(
        f"Table 19 shows that all of the components of the system were rated ⟦{label(overall)}⟧, with component "
        f"means ranging from ⟦{min(ms)}⟧ to ⟦{max(ms)}⟧ and an overall weighted mean of ⟦{overall}⟧. These "
        "results show that the system was judged by its administrators, customers, and IT experts to be "
        "functional, of good quality, and easy to use."))
    return "".join(x)


# --------------------------------------------------------------------------
# CHAPTER V
# --------------------------------------------------------------------------

def numbered(n, lead, text):
    return para(run(f"{n}. {lead} ", b=True) + runs(text), left=504, hanging=504, first=0, line=480, after=0)


def M(v):
    return f"⟦{v}⟧"


def chapter_v():
    x = []
    x.append(chapter_title("CHAPTER V", page_break=True))
    x.append(chapter_title("SUMMARY OF FINDINGS, CONCLUSIONS, AND RECOMMENDATIONS"))
    x.append(heading("SUMMARY OF FINDINGS"))
    x.append(body(
        "This chapter presents the summary of findings, conclusions, and recommendations of the study entitled "
        "“Mera’s General Merchandise Store Management System with POS and Chat Support.” The system was developed "
        "to replace the manual handling of sales, inventory, reports, and customer communication at Mera’s General "
        "Merchandise Store by providing a web portal for the administrator, a customer website with inquiry and "
        "chatbot support, and an Android mobile application. It was developed using the Agile (Scrum-based) model "
        f"and was evaluated by {N_ALL} respondents composed of the store owner and merchants, the store employees, "
        "the customers, and the IT experts, using the ISO/IEC 25010-based questionnaire and the usability "
        "questionnaire described in Chapter III."))
    mins = min(m for _, _, m in summary_rows)
    maxs = max(m for _, _, m in summary_rows)
    x.append(body(
        f"The Chapter IV evaluation showed that the system obtained an overall weighted mean of {M(overall)}, "
        f"interpreted as {M(label(overall))}. The component-level weighted means ranged from {M(mins)} to "
        f"{M(maxs)}, indicating that the system was able to support the intended sales, inventory, reporting, and "
        "customer-communication processes of the store. The major findings are summarized below."))

    parts = [f"{M(n)} {SHORT[g]} ({M(str(pct(n, N_ALL)) + '%')})" for g, n in GROUPS]
    top = max(GROUPS, key=lambda g: g[1])[0]
    x.append(numbered(
        1, "Profile of the respondents.",
        f"The evaluation involved {M(N_ALL)} respondents: {join_and(parts)}. "
        + ("The customers were the largest group, so the system was evaluated mainly by its intended end "
           "users, while the IT experts assessed its software quality." if top == "Customers" else
           "The respondents include the people who operate the system, those who use it, and the IT experts "
           "who assessed its software quality.")))

    def best_worst(items, noun):
        hi_t, hi, lo_t, lo = extremes(items)
        return (f"{sentence_start(join_and(hi_t))} received the highest indicator mean of {M(hi)}, while "
                f"{join_and(lo_t)} received the lowest indicator mean of {M(lo)}, showing an opportunity for "
                f"further refinement of the {noun}.")

    x.append(numbered(
        2, "Administrator dashboard.",
        "The dashboard that displays the daily, weekly, and monthly sales, the total revenue, the number of "
        "products, the low-stock count and alerts, the recent sales, and the sales and product charts obtained a "
        f"total weighted mean of {M(dash_total)}. This indicates that the dashboard gives the store owner "
        "accessible and accurate information about the operation of the store. "
        + best_worst(dash_items, "dashboard")))
    x.append(numbered(
        3, "Administrator management functions.",
        "The functions for managing products and inventory, importing products, bulk pricing, POS checkout, "
        "cash and GCash payment, receipts, sales reports, customer inquiries, support chat, user accounts, "
        f"activity logs, and system settings achieved a total weighted mean of {M(admin_total)}. These results "
        "confirm that the administrator portal can support the daily transactions and record keeping of the "
        "store. " + best_worst(admin_items, "administrator portal")))
    x.append(numbered(
        4, "Customer website.",
        "The functions for viewing and searching the product catalogue, checking prices and stock status, sending "
        "inquiries, registering and logging in, recovering a password, receiving notifications, and using the "
        f"chatbot obtained a total weighted mean of {M(cust_total)}. This shows that customers can obtain "
        "information from the store and communicate with it through the website. "
        + best_worst(cust_items, "customer website")))
    x.append(numbered(
        5, "Android mobile application.",
        f"The Android application obtained a total weighted mean of {M(mob_total)}, which indicates that "
        "providing access through a mobile device supports the convenience and portability of the system, "
        "including the push notification of the store's replies to inquiries. "
        + best_worst(mob_items, "mobile application")))
    ihi_t, ihi, ilo_t, ilo = extremes(iso_items)
    x.append(numbered(
        6, "Software quality based on ISO/IEC 25010.",
        f"The IT experts rated the quality of the system with a total weighted mean of {M(iso_total)}. "
        f"{join_and(ihi_t)} received the highest mean of {M(ihi)}, while {join_and(ilo_t).lower()} received "
        f"the lowest mean of {M(ilo)}, interpreted as {M(label(ilo))}. The system is therefore functional, "
        "secure, and reliable for the needs of the store, with the lowest-rated characteristics offering the "
        "greatest opportunity for improvement."))
    uhi_t, uhi, ulo_t, ulo = extremes(use_items)
    x.append(numbered(
        7, "Usability.",
        f"The store owner, the store employees, and the customers rated the usability of the system with a total "
        f"weighted mean of {M(use_total)}. {join_and(uhi_t)} received the highest mean of {M(uhi)}, while "
        f"{join_and(ulo_t).lower()} received {M(ulo)}. This confirms that the system is useful, satisfying, and "
        "easy to learn for the people who use it."))

    # Conclusions --------------------------------------------------------
    x.append(heading("CONCLUSIONS"))
    lower_names = join_and([i["text"].lower() for i in iso_items if label(i["mean"]) != SCALE[0][1]]) \
        or "its lowest-rated characteristics"
    x.append(body(
        "Based on the findings, the researchers conclude that Mera’s General Merchandise Store Management System "
        "with POS and Chat Support achieved its intended purpose of providing the store with a centralized and "
        f"reliable platform for managing products, inventory, point-of-sale transactions, sales reports, and "
        f"customer communication. The {M(label(overall))} rating of the evaluated components shows that the "
        "system can record sales accurately, deduct sold items from the inventory automatically, monitor "
        "low-stock products, produce sales reports, and respond to customer inquiries in an organized and "
        "efficient manner."))
    x.append(body(
        "The system also supports the needs of its different users. The administrator, who handles the POS, can "
        "manage products, process cash and GCash sales, print receipts, generate reports, answer customer "
        "inquiries, and monitor user accounts and activity logs, while customers can browse the catalogue, send "
        "inquiries, receive replies, and use the chatbot through the website or the Android application. These "
        "capabilities improve the record keeping, the speed of service, and the communication of the store with "
        "its customers."))
    x.append(body(
        f"The ISO/IEC 25010 results show that the system is functional, secure, and usable, while "
        f"{lower_names} received lower ratings than the other characteristics. The positive usability results "
        "also confirm that the store owner, the store employees, and the customers find the system useful, "
        "satisfying, and easy to use and learn. Continued maintenance, user guidance, and regular review of "
        "records will be important to sustain the usefulness and reliability of the system after deployment."))

    # Recommendations ----------------------------------------------------
    x.append(heading("RECOMMENDATIONS"))
    x.append(body(
        "To sustain and further improve Mera’s General Merchandise Store Management System with POS and Chat "
        "Support, the researchers recommend the following actions:"))
    eff = next(i["mean"] for i in iso_items if i["text"] == "Efficiency")
    mnt = next(i["mean"] for i in iso_items if i["text"] == "Maintainability")
    recs = [
        ("Improve system efficiency.",
         f"Efficiency received one of the lowest quality ratings ({M(eff)}). The researchers recommend optimizing "
         "database queries, adding indexes and caching for the dashboard, reports, and product catalogue, "
         "compressing product images, and paginating long lists so that pages load and respond faster as the "
         "number of products and sales records grows."),
        ("Strengthen maintainability.",
         f"Maintainability received a mean of {M(mnt)}. Technical documentation, consistent coding standards, "
         "and automated tests for the POS, inventory, report, and account modules should be prepared so that "
         "errors can be corrected and features can be updated with less risk. The modular structure and the "
         "version control of the code should be maintained."),
        ("Integrate an online payment gateway.",
         "The POS currently displays a GCash QR code and relies on the cashier to confirm the payment. "
         "Integrating an official GCash or other payment gateway will allow payments to be confirmed "
         "automatically, reference numbers to be recorded, and digital receipts to be generated, which reduces "
         "errors and speeds up the checkout."),
        ("Expand user roles and online ordering.",
         "Separate Cashier and Merchant accounts with limited access should be added, and customers should be "
         "allowed to place orders or reservations through the website and the mobile application. This will "
         "improve the accountability for sales and extend the system from information and inquiries to complete "
         "online transactions."),
        ("Enhance the mobile application and chat support.",
         "Native features of the Android application such as in-app notifications, offline viewing of the "
         "product catalogue, and an iOS version should be developed. The answers of the chatbot should be "
         "expanded and a chat history should be kept in the account of the customer."),
        ("Provide user orientation and support.",
         "Orientation sessions and concise user guides should be prepared for the administrator, the store "
         "employees, and the customers, particularly on POS checkout, product and stock updates, report "
         "generation, inquiry handling, account recovery, and installing the Android application."),
        ("Maintain security and data privacy.",
         "Hashed passwords, role-based access, and activity logs should be kept, stronger password rules should "
         "be required, the database should be backed up regularly using the Export & Backup function, and the "
         "framework and libraries should be updated regularly. Customer information should continue to be "
         "handled in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173)."),
        ("Conduct continuing evaluation.",
         "Feedback should be collected regularly from the store owner, the employees, and the customers after "
         "deployment and used to guide improvements. A future evaluation may involve a larger number of "
         "respondents and a broader ISO/IEC 25010 assessment that also covers compatibility and portability."),
    ]
    for n, (lead, text) in enumerate(recs, 1):
        x.append(numbered(n, lead, text))
    return "".join(x)


# --------------------------------------------------------------------------
# Package handling
# --------------------------------------------------------------------------

def read_package(path):
    """Read every member; tolerate zip entries whose stored CRC is 0."""
    members = {}
    with zipfile.ZipFile(path) as z, open(path, "rb") as raw_f:
        for zi in z.infolist():
            try:
                data = z.read(zi)
            except zipfile.BadZipFile:
                raw_f.seek(zi.header_offset)
                head = raw_f.read(30)
                n, m = struct.unpack("<HH", head[26:30])
                raw_f.seek(zi.header_offset + 30 + n + m)
                raw = raw_f.read(zi.compress_size)
                data = zlib.decompress(raw, -15) if zi.compress_type == zipfile.ZIP_DEFLATED else raw
            if len(data) != zi.file_size:
                raise SystemExit(f"{zi.filename}: size mismatch ({len(data)} vs {zi.file_size})")
            members[zi.filename] = data
    return members


# --------------------------------------------------------------------------
# CHAPTER III - system flowchart section and ERD renumbering
# --------------------------------------------------------------------------
REL_IMAGE = "http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"
TEXT_W_IN, MAX_H_IN = 5.6, 8.0

CH3_INTRO = (
    "The flowcharts in this section show the logic of the system from the first visit of a user to the end "
    "of the session. Figure {f0} presents the overall flow of the system, while Figures {f1} to {f2} present "
    "the detailed flow of each process. In the flowcharts, the green rounded shapes mark the start and end "
    "of a process, the blue rectangles are processes, the yellow diamonds are decisions, the cylinders are "
    "the data stores (D1 Users, D2 Products, D3 Inquiries, D4 Messages, D5 Sales, D6 Sale Items, D7 Activity "
    "Logs, and D8 Password Reset Tokens), and the orange boxes direct the reader to the flowcharts of the "
    "processes that follow.")

CH3_DESC = {
    "overall": (
        "Figure {n} shows the overall flow of the system. A user opens the MERAS website or the Android "
        "application and either registers a new customer account or logs in with an existing account. If the "
        "credentials are not valid, an error is shown and the user may retry or recover the password. After a "
        "successful login, the system checks the role of the user: an administrator is directed to the admin "
        "operations (inventory, POS, inquiries, dashboard, reports, users, activity logs, and settings), while "
        "a customer is directed to the customer operations (browsing the products, sending inquiries, and "
        "using the chat). The session ends when the user logs out."),
    "register": (
        "Figure {n} shows the registration of a customer account. The customer fills in the full name, email "
        "address, password, and password confirmation. The system validates the required fields, the email "
        "format, the uniqueness of the email address, and the password length. If the data are not valid, the "
        "errors are shown and the customer corrects them; otherwise, the account is saved with a hashed "
        "password, the customer is logged in automatically, and the registration is recorded in the activity "
        "log."),
    "login": (
        "Figure {n} shows the login process. The user enters the email address and password, or chooses to "
        "sign in with a Google account, and the system checks the credentials against the user records. An "
        "error message is shown when the credentials are not valid, and the user may retry or select Forgot "
        "Password. Staff accounts are not allowed to log in inside the mobile application and must use the web "
        "portal. For a valid login, the system regenerates the session, records the login in the activity "
        "log, and directs an administrator to the admin portal and a customer to the home page."),
    "recovery": (
        "Figure {n} shows how a user recovers an account. The user enters the registered email address; if no "
        "account is found, a message is shown. Otherwise, the system generates a random token, saves it in "
        "hashed form, and sends a reset link to the email address, which is valid for 60 minutes. When the "
        "user opens the link and enters a new password, the system verifies the token and its expiry and "
        "validates the password. If these are valid, the password hash is updated, the used token is deleted, "
        "and the user returns to the login page; if not, the user is asked to request a new link."),
    "browse": (
        "Figure {n} shows how a customer browses the products and sends an inquiry. The home page loads the "
        "product catalogue with the prices and the stock status, and the customer can search by name or SKU, "
        "filter by category, and switch between the grid view and the table view. A customer who wants to ask "
        "about bulk sales, availability, or pickup fills in the inquiry form with the name, email, subject, "
        "and message. After the details are validated, the pending inquiry is saved together with the device "
        "token of the mobile application, the activity is recorded, and a confirmation is shown. The reply of "
        "the store is later received by email or push notification and can be read under Profile or "
        "Notifications."),
    "chat": (
        "Figure {n} shows the chat support. The customer opens the chat widget and types a question or chooses "
        "a quick button such as Store Hours, Location, Products, or Payment. The question is sent to the "
        "chatbot, which determines the intent and saves the conversation when the customer is logged in. For "
        "product-related questions, the system searches the matching products and their stock. The chatbot "
        "returns a reply with suggestions and product cards, and when live help is needed, it offers the "
        "Facebook Messenger link. The customer may ask another question until the conversation ends."),
    "inventory": (
        "Figure {n} shows the management of the inventory. The administrator views and searches the product "
        "list and then chooses to add or edit, import, or delete products. In adding or editing, the product "
        "form is validated (the name, price, and stock are required, and a bulk price must be given together "
        "with the quantity at which it starts) before the product is inserted or updated. In importing, a CSV "
        "file is validated and each row is processed, adding new products with generated SKUs and updating "
        "existing products by name. In deleting, the administrator confirms the deletion before the record is "
        "removed. Every action is recorded in the activity log."),
    "pos": (
        "Figure {n} shows the flow of a POS transaction. The administrator searches the catalogue and selects "
        "a product and quantity. The system checks the available stock; if the product is out of stock, a "
        "message is shown. Otherwise, the item is added to the cart, limited to the available stock, and the "
        "bulk price is applied when the bulk quantity is reached. When no more items are to be added, the "
        "payment method is selected. For a GCash payment, the GCash QR code is shown and the payment is "
        "confirmed; for a cash payment, the cash tendered must be at least equal to the total. The system then "
        "saves the sale and its items in one database transaction, decreases the product stock, records the "
        "sale in the activity log, computes the change, and shows the printable receipt."),
    "inquiry": (
        "Figure {n} shows how the administrator handles customer inquiries. The inquiry history can be "
        "searched and filtered by status (Pending or Responded). The administrator may respond to an inquiry, "
        "switch its status between Pending and Responded, or delete it. When a response is saved, the inquiry "
        "is marked as Responded together with the responding administrator, and the reply is sent to the "
        "customer by email and, when the device token of the customer is saved, by push notification to the "
        "Android application. The action is then recorded in the activity log."),
    "dashboard": (
        "Figure {n} shows the flow of the dashboard and the sales reports. The dashboard reads the daily, "
        "weekly, and monthly sales, the total revenue, and the number of products, and displays them with the "
        "seven-day sales trend, the top-selling products, the stock by category, the recent sales, and the "
        "low-stock alerts for products with a stock of less than five. In the sales reports, the administrator "
        "selects the period (weekly, monthly, yearly, or all records), and the system computes and displays "
        "the revenue, the number of transactions, the average sale, the top-selling products, and the recent "
        "transactions, which can be downloaded as a CSV file or printed."),
    "admin": (
        "Figure {n} shows the flow of the user accounts, the activity logs, and the system settings. In the "
        "user accounts, the administrator views and searches the accounts and creates an account, changes a "
        "role, or deletes an account; the system allows only one administrator and does not allow the "
        "administrator to change the role of or delete the own account. The activity logs can be filtered by "
        "role or searched by user, action, details, or IP address. In the system settings, the administrator "
        "views the system overview, exports and backs up the records to a CSV file, changes the administrator "
        "password, or runs a maintenance action such as seeding demo products, resetting the sales history, or "
        "clearing the chat history. Changes are recorded in the activity log."),
}


def png_size(data):
    assert data[:8] == b"\x89PNG\r\n\x1a\n"
    return struct.unpack(">II", data[16:24])


def inline_image(rid, cx, cy, doc_id, name):
    a = "http://schemas.openxmlformats.org/drawingml/2006/main"
    return ('<w:p><w:pPr><w:pStyle w:val="style0"/><w:keepNext/>'
            '<w:spacing w:before="120" w:after="60" w:line="240" w:lineRule="auto"/><w:jc w:val="center"/></w:pPr>'
            '<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            f'<wp:extent cx="{cx}" cy="{cy}"/><wp:effectExtent l="0" t="0" r="0" b="0"/>'
            f'<wp:docPr id="{doc_id}" name="{name}"/>'
            f'<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="{a}" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            f'<a:graphic xmlns:a="{a}"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr>'
            f'<pic:cNvPr id="0" name="{name}"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill>'
            f'<a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            f'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm>'
            '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic>'
            '</wp:inline></w:drawing></w:r></w:p>')


def fit_emu(w_px, h_px, max_w=TEXT_W_IN, max_h=MAX_H_IN):
    width_in = min(max_w, max_h * w_px / h_px)
    return int(width_in * 914400), int(width_in * h_px / w_px * 914400)


def para_bounds(doc, idx):
    start = max(doc.rfind("<w:p>", 0, idx), doc.rfind("<w:p ", 0, idx))
    return start, doc.find("</w:p>", idx) + 6


DIAGRAM_SLOTS = {  # figure number -> text that starts the paragraph describing the figure
    14: "Figure 14 shows the phases",
    15: "Figure 15 shows the architecture",
    16: "Figure 16 shows the use case",
    17: "Figure 17 presents the context",
}


def replace_diagram_slots(doc, pkg):
    """Figures 14-16 held placeholder rectangles and wrong flowchart fragments, all pinned to page
    coordinates. Remove them and place the real diagrams inline, directly above their captions."""
    for idx, (key, no, caption, name) in enumerate(FIGURES):
        if key == "erd":
            continue
        i = doc.find(DIAGRAM_SLOTS[no])
        assert i > 0, f"description of Figure {no} not found"
        ps = para_bounds(doc, i)[0]
        cm = re.search(rf"<w:t[^>]*>Figure {no}\. {re.escape(caption)}</w:t>", doc[ps:])
        assert cm, f"caption of Figure {no} not found"
        cps = para_bounds(doc, ps + cm.start())[0]
        seg = doc[ps:cps]
        old_rids = set(re.findall(r'r:embed="(rId\d+)"', seg))
        seg = re.sub(r"<mc:AlternateContent>.*?</mc:AlternateContent>", "", seg, flags=re.S)  # placeholder shapes
        seg = re.sub(r"<w:drawing>.*?</w:drawing>", "", seg, flags=re.S)                     # wrong pictures
        assert "<w:pict" not in seg and "<w:drawing" not in seg
        paras = re.findall(r"<w:p[ >].*?</w:p>", seg, flags=re.S)
        assert "".join(paras) == seg, f"unexpected content around Figure {no}"
        kept = [paras[0]] + [p for p in paras[1:] if re.search(r"<w:t[ >]", p)]  # drop the spacer paragraphs

        data = (FIG_DIR / name).read_bytes()
        pkg[f"word/media/{name}"] = data
        rid = f"rIdDg{no}"
        rels = pkg["word/_rels/document.xml.rels"].decode("utf-8")
        rels = rels.replace("</Relationships>",
                            f'<Relationship Id="{rid}" Type="{REL_IMAGE}" Target="media/{name}"/></Relationships>')
        cx, cy = fit_emu(*png_size(data))
        doc = doc[:ps] + "".join(kept) + inline_image(rid, cx, cy, 3200 + no, f"Figure{no}") + doc[cps:]

        for old in old_rids:  # the wrong pictures are no longer referenced: drop them from the package
            if f'r:embed="{old}"' not in doc:
                m = re.search(rf'<Relationship Id="{old}"[^>]*Target="(media/[^"]+)"[^>]*/>', rels)
                if m:
                    rels = rels.replace(m.group(0), "")
                    pkg.pop("word/" + m.group(1), None)
        pkg["word/_rels/document.xml.rels"] = rels.encode("utf-8")
    return doc


def keep_header_rows_with_next(doc):
    """Stop a table's header row from being stranded alone at the bottom of a page."""
    def fix_row(row):
        return re.sub(r'(<w:pPr>(?:<w:pStyle w:val="[^"]*"/>)?)(?!<w:keepNext)', r"\g<1><w:keepNext/>",
                      row.group(0))

    def fix_table(tbl):
        return re.sub(r"<w:tr[ >].*?</w:tr>", fix_row, tbl.group(0), count=1, flags=re.S)

    return re.sub(r"<w:tbl>.*?</w:tbl>", fix_table, doc, flags=re.S)


CH3_TEXT = {  # paragraphs of Chapter III that describe the diagrams, rewritten for the system as built
    "Figure 15 shows": (
        "Figure 15 shows the architecture of the system. The web browser, which serves the admin portal and "
        "the customer website, and the Android mobile application communicate with the application server "
        "through secure requests. The application server processes the business logic, including "
        "authentication, POS transactions, inventory, reports, inquiries, and chat, and stores all data in "
        "the MySQL database. It also connects to external services for email, push notifications, and "
        "Google sign-in."),
    "Figure 16 shows": (
        "Figure 16 shows the use case diagram of the system and how the two types of users, the "
        "administrator and the customer, interact with its main functions. Table 4 summarizes the access of "
        "each user type to the system modules."),
    "Figure 17 presents": (
        "Figure 17 presents the context diagram, which shows the system as a single process and the data "
        "that flow between the system and its external entities: the administrator, the customer, Google "
        "Sign-In, the email service, and the push notification service."),
}

TABLE2_ROWS = [
    ("Administrator",
     "Create sales records and process transactions (cash or GCash QR code); read and respond to customer "
     "inquiries; create, update, and import products, stock quantities, and prices; delete discontinued "
     "products, inactive accounts, and spam inquiries; clear the sales and chat history; view the dashboard; "
     "generate, export (CSV), and print sales reports; view the activity logs; manage user accounts and "
     "system settings; change the administrator password and recover the account through the registered "
     "email address."),
    ("Customer",
     "Register an account; log in with an email address and password or with a Google account; recover the "
     "account through a password reset link; browse, search, and filter the products and check their prices "
     "and stock status; send inquiries to the store; use the chatbot and chat support; read the reply of the "
     "store, received by email or push notification, under Notifications; download and use the Android "
     "application."),
]
TABLE4_ROWS = [
    ("POS Checkout", "Yes", "No"), ("Inventory Management", "Yes", "No"), ("Dashboard", "Yes", "No"),
    ("Sales Reports", "Yes", "No"), ("User and Record Management", "Yes", "No"), ("Activity Logs", "Yes", "No"),
    ("Inquiries and Chat Support", "Reply", "Send"), ("Browse Products", "Yes", "Yes"),
    ("GCash Payment (QR code at the POS)", "Yes", "No"),
]
TABLE5_ROWS = [
    ("users", "Stores account information (name, email address, hashed password) and the role of each account "
              "(administrator or customer)."),
    ("products", "Stores product details (SKU, name, unit, category), retail and bulk prices, the bulk "
                 "quantity, and the stock quantities used for low-stock alerts."),
    ("sales", "Stores each completed sales transaction, including the date, the total amount, and the user "
              "who processed it."),
    ("sale_items", "Stores the products, quantities, and prices included in each sale."),
    ("inquiries", "Stores customer inquiries (name, email address, subject, message), the device token for "
                  "push notification, the status (pending or responded), the response, and who responded "
                  "and when."),
    ("messages", "Stores the messages of the team conversation in the chat support."),
    ("activity_logs", "Stores the actions of administrators, customers, and guests with the user, role, "
                      "details, IP address, and browser, as an audit trail."),
    ("password_reset_tokens", "Stores the hashed password reset tokens with the email address and the time "
                              "they were created, which determines their expiry."),
]


def simple_table(widths, header, rows, aligns):
    out = [row(widths, header, ["center"] * len(widths), header=True)]
    out += [row(widths, list(r), aligns) for r in rows]
    return table(widths, out)


def replace_table_after(doc, caption, new_table):
    """Replace the table that follows the paragraph holding `caption`."""
    i = doc.find(caption)
    assert i > 0, f"caption not found: {caption}"
    end = para_bounds(doc, i)[1]
    t0 = doc.find("<w:tbl>", end)
    assert 0 <= t0 - end < 400, f"table after {caption} not where expected"
    t1 = doc.find("</w:tbl>", t0) + len("</w:tbl>")
    return doc[:t0] + new_table + doc[t1:]


def replace_run_text(doc, start_text, new_text):
    """Rewrite the paragraph whose text begins with `start_text` (exactly one in the document).

    The old wording may be spread over several runs, so the first text run gets the new sentence and the
    others in that paragraph are emptied."""
    pat = re.compile(r"<w:t[^>]*>" + re.escape(start_text))
    hits = pat.findall(doc)
    assert len(hits) == 1, f"paragraph not unique/found: {start_text}"
    i = pat.search(doc).start()
    ps, pe = para_bounds(doc, i)
    para = doc[ps:pe]
    first = [True]

    def swap(m):
        if first[0]:
            first[0] = False
            return m.group(1) + escape(new_text) + m.group(3)
        return m.group(1) + m.group(3)

    para = re.sub(r"(<w:t[^>]*>)([^<]*)(</w:t>)", swap, para)
    return doc[:ps] + para + doc[pe:]


def update_chapter3_as_built(doc):
    for start, text in CH3_TEXT.items():
        doc = replace_run_text(doc, start, text)
    doc = replace_table_after(doc, "Table 2. Functional Requirements by User",
                              simple_table([1900, 6406], ["User", "Functional Requirements"], TABLE2_ROWS,
                                           ["left", "left"]))
    doc = replace_table_after(doc, "Table 4. Access of Users to System Modules",
                              simple_table([4306, 2000, 2000], ["Module", "Admin", "Customer"], TABLE4_ROWS,
                                           ["left", "center", "center"]))
    doc = replace_table_after(doc, "Table 5. Description of Database Tables",
                              simple_table([2900, 5406], ["Table", "Description"], TABLE5_ROWS, ["left", "left"]))
    return doc


def patch_chapter3(doc, pkg):
    """Replace the single POS flowchart with the complete flowchart set and renumber the ERD."""
    doc = replace_diagram_slots(doc, pkg)
    doc = update_chapter3_as_built(doc)
    doc = keep_header_rows_with_next(doc)
    # 1. the 'System Flowchart' section: heading stays, intro + POS picture + caption are replaced
    h = doc.find(">System Flowchart<")
    cm = re.search(r"<w:t[^>]*>Figure 18\. POS Transaction Flowchart</w:t>", doc)  # the caption text, not alt text
    c = cm.start() if cm else -1
    assert h > 0 and c > h, "System Flowchart section not found in Chapter III"
    h_end = para_bounds(doc, h)[1]
    c_end = para_bounds(doc, c)[1]
    rels = pkg["word/_rels/document.xml.rels"].decode("utf-8")
    old_rid = re.search(r'Id="(rId\d+)"[^>]*Target="media/image5\.png"', rels)
    old_rid = old_rid.group(1) if old_rid else None

    new = [body(CH3_INTRO.format(f0=FIRST_FIGURE, f1=FIRST_FIGURE + 1, f2=FIRST_FIGURE + len(KEYS) - 1))]
    new_rels = ""
    for i, key in enumerate(KEYS):
        data = (FC_DIR / fc_file(key)).read_bytes()
        member = f"word/media/{fc_file(key)}"
        pkg[member] = data
        rid = f"rIdFc{i + 1}"
        new_rels += f'<Relationship Id="{rid}" Type="{REL_IMAGE}" Target="media/{fc_file(key)}"/>'
        cx, cy = fit_emu(*png_size(data))
        no = FIRST_FIGURE + i
        new.append(body(CH3_DESC[key].replace("{n}", str(no))))
        new.append(inline_image(rid, cx, cy, 3000 + i, f"Flowchart{no}"))
        new.append(figure_caption(f"Figure {no}. {CAPTIONS[key]}"))
    doc = doc[:h_end] + "".join(new) + doc[c_end:]

    # 2. relationships / media: drop the old POS picture, add the new flowcharts
    if old_rid:
        assert f'r:embed="{old_rid}"' not in doc, "old POS picture is still referenced"
        rels = re.sub(rf'<Relationship Id="{old_rid}"[^>]*/>', "", rels)
        pkg.pop("word/media/image5.png", None)
    pkg["word/_rels/document.xml.rels"] = rels.replace("</Relationships>", new_rels + "</Relationships>").encode("utf-8")
    assert 'extension="png"' in pkg["[Content_Types].xml"].decode("utf-8").lower()

    # 3. ERD: renumber, new as-built diagram, inline so it survives the longer section
    e = doc.find("Figure 19 shows the entity relationship diagram")
    assert e > 0, "ERD paragraph not found"
    doc = replace_run_text(
        doc, "Figure 19 shows the entity relationship diagram",
        f"Figure {ERD_NO} shows the entity relationship diagram of the MySQL database, and Table 5 describes "
        "each table. The design links users, products, sales, sale items, inquiries, messages, activity logs, "
        "and password reset tokens so that every transaction, communication, and user action can be traced "
        "and reported accurately.")
    pe = para_bounds(doc, doc.find(f"Figure {ERD_NO} shows the entity relationship diagram"))[1]
    d = doc.find("<w:drawing>", pe)
    qs, qe = para_bounds(doc, d)
    pic = doc[qs:qe]
    assert qs - pe < 40 and not re.sub(r"<[^>]+>", "", pic).strip(), "ERD picture paragraph not where expected"
    old_rid = re.search(r'r:embed="(rId\d+)"', pic).group(1)
    erd_file = next(f[3] for f in FIGURES if f[0] == "erd")
    data = (FIG_DIR / erd_file).read_bytes()
    pkg[f"word/media/{erd_file}"] = data
    rels = pkg["word/_rels/document.xml.rels"].decode("utf-8")
    rels = rels.replace("</Relationships>",
                        f'<Relationship Id="rIdDgErd" Type="{REL_IMAGE}" Target="media/{erd_file}"/></Relationships>')
    cx, cy = fit_emu(*png_size(data))
    doc = doc[:qs] + inline_image("rIdDgErd", cx, cy, 3100, "ERD") + doc[qe:]
    if f'r:embed="{old_rid}"' not in doc:                      # drop the old picture from the package
        m = re.search(rf'<Relationship Id="{old_rid}"[^>]*Target="(media/[^"]+)"[^>]*/>', rels)
        if m:
            rels = rels.replace(m.group(0), "")
            pkg.pop("word/" + m.group(1), None)
    pkg["word/_rels/document.xml.rels"] = rels.encode("utf-8")
    assert doc.count("Figure 19. Entity Relationship Diagram") == 1
    doc = doc.replace("Figure 19. Entity Relationship Diagram", f"Figure {ERD_NO}. Entity Relationship Diagram")
    return doc


def main():
    if not SRC.exists():
        sys.exit(f"Source not found: {SRC}")
    pkg = read_package(SRC)
    doc = pkg["word/document.xml"].decode("utf-8")
    m = re.search(r"<w:sectPr>.*?</w:sectPr></w:body></w:document>\s*$", doc, flags=re.S)
    if not m:
        sys.exit("Could not find the final section properties in document.xml")
    doc = patch_chapter3(doc, pkg)
    m = re.search(r"<w:sectPr>.*?</w:sectPr></w:body></w:document>\s*$", doc, flags=re.S)
    new_doc = doc[:m.start()] + chapter_iv() + chapter_v() + m.group(0)
    pkg["word/document.xml"] = new_doc.encode("utf-8")

    order = ["[Content_Types].xml", "_rels/.rels"] + [k for k in pkg if k not in ("[Content_Types].xml", "_rels/.rels")]
    target = OUT
    for attempt in range(2, 50):
        try:
            with zipfile.ZipFile(target, "w", zipfile.ZIP_DEFLATED) as z:
                for name in order:
                    z.writestr(name, pkg[name])
            break
        except PermissionError:  # the file is open in Word: write a numbered copy instead
            target = OUT.with_name(f"{OUT.stem}-v{attempt}{OUT.suffix}")
    print(f"Wrote {target}")
    print(f"Overall weighted mean (sample): {overall} - {label(overall)}")
    for name, n, mval in summary_rows:
        print(f"  {name}: n={n}, mean={mval} ({label(mval)})")


if __name__ == "__main__":
    main()
