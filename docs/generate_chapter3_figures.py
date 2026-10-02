"""
Generates the Chapter III design diagrams of Mera's General Merchandise Store Management System,
all showing the system as it is actually built (see the controllers, views and migrations):

    Figure 14  Agile Development Model Applied in the Study
    Figure 15  System Architecture
    Figure 16  Use Case Diagram
    Figure 17  Context Diagram
    Figure 29  Entity Relationship Diagram   (number follows the flowcharts, see FIRST_FIGURE)

Run:  python docs/generate_chapter3_figures.py
"""
import math
import sys
from pathlib import Path

from PIL import Image, ImageChops, ImageDraw

sys.path.insert(0, str(Path(__file__).resolve().parent))
from flowchart_engine import INK, LINE, SS, STYLE, Chart, load_font  # noqa: E402
from generate_chapter3_flowcharts import FIRST_FIGURE, KEYS  # noqa: E402

OUT = Path(__file__).resolve().parent / "chapter-3-diagrams" / "figures"
ERD_NO = FIRST_FIGURE + len(KEYS)

FIGURES = [  # (key, figure number in Chapter III, caption, file name)
    ("agile", 14, "Agile Development Model Applied in the Study", "fig-14-agile-model.png"),
    ("architecture", 15, "System Architecture", "fig-15-system-architecture.png"),
    ("usecase", 16, "Use Case Diagram", "fig-16-use-case-diagram.png"),
    ("context", 17, "Context Diagram", "fig-17-context-diagram.png"),
    ("erd", ERD_NO, "Entity Relationship Diagram", "fig-erd-entity-relationship-diagram.png"),
]


# ---------------------------------------------------------------------------------------
# Small Pillow canvas helper (logical units, supersampled)
# ---------------------------------------------------------------------------------------
class Canvas:
    def __init__(self, w, h):
        self.w, self.h = w, h
        self.img = Image.new("RGB", (w * SS, h * SS), "#FFFFFF")
        self.d = ImageDraw.Draw(self.img)
        self.f, self.fb = load_font(22), load_font(22, True)

    def s(self, v):
        return v * SS

    def line(self, pts, width=2, fill=LINE, dash=False):
        for p, q in zip(pts, pts[1:]):
            if not dash:
                self.d.line((self.s(p[0]), self.s(p[1]), self.s(q[0]), self.s(q[1])), fill=fill, width=self.s(width))
                continue
            length = math.hypot(q[0] - p[0], q[1] - p[1])
            ux, uy = (q[0] - p[0]) / length, (q[1] - p[1]) / length
            pos = 0.0
            while pos < length:
                end = min(pos + 10, length)
                self.d.line((self.s(p[0] + ux * pos), self.s(p[1] + uy * pos), self.s(p[0] + ux * end),
                             self.s(p[1] + uy * end)), fill=fill, width=self.s(width))
                pos += 17

    def head(self, tip, frm, size=15, half=7.5):
        dx, dy = tip[0] - frm[0], tip[1] - frm[1]
        n = math.hypot(dx, dy) or 1
        dx, dy = dx / n, dy / n
        bx, by = tip[0] - dx * size, tip[1] - dy * size
        px, py = -dy, dx
        self.d.polygon([(self.s(tip[0]), self.s(tip[1])), (self.s(bx + px * half), self.s(by + py * half)),
                        (self.s(bx - px * half), self.s(by - py * half))], fill=LINE)

    def arrow(self, p, q, dash=False):
        self.line([p, q], dash=dash)
        self.head(q, p)

    def wrap(self, text, font, maxw):
        out = []
        for part in text.split("\n"):
            cur = ""
            for word in part.split():
                trial = (cur + " " + word).strip()
                if not cur or font.getlength(trial) / SS <= maxw:
                    cur = trial
                else:
                    out.append(cur)
                    cur = word
            out.append(cur)
        return out

    def text(self, cx, cy, lines, bold=False, lh=27, anchor="mm", bg=False):
        font = self.fb if bold else self.f
        if isinstance(lines, str):
            lines = [lines]
        y = cy - len(lines) * lh / 2 + lh / 2
        if bg:
            tw = max(font.getlength(l) / SS for l in lines)
            self.d.rectangle((self.s(cx - tw / 2 - 4), self.s(cy - len(lines) * lh / 2 - 2),
                              self.s(cx + tw / 2 + 4), self.s(cy + len(lines) * lh / 2 + 2)), fill="#FFFFFF")
        for line in lines:
            self.d.text((self.s(cx), self.s(y)), line, font=font, fill=INK, anchor=anchor)
            y += lh

    def box(self, x1, y1, x2, y2, kind="proc", radius=6, width=2):
        fill, outline = STYLE[kind]
        self.d.rounded_rectangle((self.s(x1), self.s(y1), self.s(x2), self.s(y2)), radius=self.s(radius),
                                 fill=fill, outline=outline, width=self.s(width))

    def save(self, path):
        bbox = ImageChops.difference(self.img, Image.new("RGB", self.img.size, "#FFFFFF")).getbbox()
        pad = 16 * SS
        img = self.img.crop((max(0, bbox[0] - pad), max(0, bbox[1] - pad), min(self.img.width, bbox[2] + pad),
                             min(self.img.height, bbox[3] + pad)))
        Path(path).parent.mkdir(parents=True, exist_ok=True)
        img.save(path, optimize=True)
        return img.size


# ---------------------------------------------------------------------------------------
# Figure 14 - Agile (Scrum) development model
# ---------------------------------------------------------------------------------------
def agile(path):
    c = Chart(cols=3, width=1280, side_w=340, center_w=340)
    c.n("p1", "proc", "Identify the problems, define the objectives and scope, and prepare the "
                      "prioritized product backlog", 0, 0, title="1. Planning and Requirements Gathering")
    c.n("p2", "proc", "Prepare the system architecture, use case diagram, context diagram, flowcharts, "
                      "ERD, and user interface layouts", 1, 0, title="2. System Design")
    c.n("p3", "proc", "Build the backlog features in sprints; each sprint delivers modules that are "
                      "coded, integrated, and reviewed", 2, 0, title="3. Development")
    c.n("mid", "ref", "Sprint 1: Account management and security\nSprint 2: Product and inventory management\n"
                      "Sprint 3: POS and payment\nSprint 4: Dashboard and reports\n"
                      "Sprint 5: Mobile application and chat support\nSprint 6: Integration and refinement",
        1, 1, w=660, title="Sprints 1 to 6 (Table 1): phases 3 to 6 repeat in every sprint")
    c.n("p4", "proc", "Test each sprint output for correctness, security, and usability, and correct "
                      "the errors found", 2, 2, title="4. Testing")
    c.n("p5", "proc", "Install the tested system for the store, together with the Android "
                      "application", 1, 2, title="5. Deployment")
    c.n("p6", "proc", "The store owner, employees, and customers use the system and give feedback",
        0, 2, title="6. Review and Feedback")
    c.e("p1", "p2", out="r", into="l")
    c.e("p2", "p3", out="r", into="l")
    c.e("p3", "p4")
    c.e("p4", "p5", out="l", into="r")
    c.e("p5", "p6", out="l", into="r")
    c.e("p6", "p1", "Next sprint: refined backlog", out="t", into="b")
    return c.render(path)


# ---------------------------------------------------------------------------------------
# Figure 15 - System architecture
# ---------------------------------------------------------------------------------------
def architecture(path):
    c = Chart(cols=2, width=1000, fs=26, side_w=430, center_w=430, row_gap=100)
    c.n("web", "proc", "Admin portal for the administrator and the customer website", 0, 0,
        title="Web Browser", min_h=170)
    c.n("app", "proc", "Customer mobile application with push notifications", 1, 0,
        title="Android Application", min_h=170)
    c.n("srv", "proc", "Authentication and role-based access  |  POS and receipts  |  Inventory  |  "
                       "Inquiries and chat  |  Dashboard and reports  |  Activity logs and settings",
        0.5, 1, w=900, title="Application Server (PHP, Laravel)")
    c.n("db", "store", "Users, products, sales, sale items, inquiries, messages, activity logs, and "
                       "password reset tokens", 0, 2, w=430, title="MySQL Database")
    c.n("ext", "ref", "Email service, push notification service, and Google Sign-In", 1, 2,
        title="External Services")
    c.e("web", "srv", "HTTPS requests", out="b", into="t", arrows="both")
    c.e("app", "srv", "HTTPS requests", out="b", into="t", arrows="both")
    c.e("srv", "db", "read and write data", out="b", into="t", arrows="both", at="end")
    c.e("srv", "ext", "email, push,\nsign-in", out="b", into="t", arrows="both", at="end")
    return c.render(path)


# ---------------------------------------------------------------------------------------
# Figure 16 - Use case diagram
# ---------------------------------------------------------------------------------------
def usecase(path):
    cv = Canvas(1200, 1300)
    ew, eh = 300, 82
    left_x, right_x = 330, 820
    customer = ["Browse, Search, and Filter Products", "Download the Android Application",
                "Register an Account", "Log In with Email or Google", "Recover Password by Email Link",
                "Submit an Inquiry", "Ask the Chatbot", "View Profile and Notifications"]
    admin = ["Log In and Change Password", "View Dashboard", "Manage Inventory and Import Products",
             "Process POS Sale and Print Receipt", "View and Export Sales Reports", "Manage User Accounts",
             "View Activity Logs", "Manage Settings and Back Up Data", "Manage and Reply to Inquiries",
             "Send Reply by Email and Push Notification"]
    y0, c_step, a_step = 160, 128, 112
    cy = lambda i: y0 + i * c_step  # noqa: E731
    ay = lambda i: y0 + i * a_step + (26 if i == 9 else 0)  # noqa: E731  (extra room for the <<include>> arrow)
    cust_mid = (cy(0) + cy(len(customer) - 1)) / 2
    adm_mid = (ay(0) + ay(len(admin) - 2)) / 2          # the administrator is not tied to the last (included) case

    cv.d.rounded_rectangle((cv.s(165), cv.s(80), cv.s(985), cv.s(ay(9) + 70)), radius=cv.s(10),
                           fill="#FFFFFF", outline=LINE, width=cv.s(3))
    cv.text(575, 45, "Mera's General Merchandise Store Management System", bold=True)

    def actor(ax, ay_, label):
        lw = cv.s(3)
        cv.d.ellipse((cv.s(ax - 17), cv.s(ay_ - 70), cv.s(ax + 17), cv.s(ay_ - 36)), outline=LINE, fill="#FFFFFF", width=lw)
        cv.line([(ax, ay_ - 36), (ax, ay_ + 12)], width=3)
        cv.line([(ax - 28, ay_ - 22), (ax + 28, ay_ - 22)], width=3)
        cv.line([(ax, ay_ + 12), (ax - 24, ay_ + 54)], width=3)
        cv.line([(ax, ay_ + 12), (ax + 24, ay_ + 54)], width=3)
        cv.text(ax, ay_ + 82, label, bold=True)

    cx_actor, ax_actor = 70, 1085
    for i in range(len(customer)):
        cv.line([(cx_actor + 30, cust_mid - 14), (left_x - ew / 2, cy(i))], width=2)
    for i in range(len(admin) - 1):
        cv.line([(ax_actor - 30, adm_mid - 14), (right_x + ew / 2, ay(i))], width=2)
    # <<include>> between replying and the e-mail / push notice
    cv.line([(right_x, ay(8) + eh / 2), (right_x, ay(9) - eh / 2 - 12)], dash=True)
    cv.head((right_x, ay(9) - eh / 2), (right_x, ay(8) + eh / 2))
    cv.text(right_x + 62, (ay(8) + ay(9)) / 2, "<<include>>", lh=24, bg=True)

    for i, t in enumerate(customer):
        cv.box(left_x - ew / 2, cy(i) - eh / 2, left_x + ew / 2, cy(i) + eh / 2, "proc", radius=eh / 2)
        cv.text(left_x, cy(i), cv.wrap(t, cv.f, ew * 0.74))
    for i, t in enumerate(admin):
        cv.box(right_x - ew / 2, ay(i) - eh / 2, right_x + ew / 2, ay(i) + eh / 2, "term", radius=eh / 2)
        cv.text(right_x, ay(i), cv.wrap(t, cv.f, ew * 0.74))
    actor(cx_actor, cust_mid, "Customer")
    actor(ax_actor, adm_mid, "Administrator")
    return cv.save(path)


# ---------------------------------------------------------------------------------------
# Figure 17 - Context diagram (level 0 data flow diagram)
# ---------------------------------------------------------------------------------------
def context(path):
    cv = Canvas(1300, 1020)
    cx, cy, r = 650, 500, 170
    cv.d.ellipse((cv.s(cx - r), cv.s(cy - r), cv.s(cx + r), cv.s(cy + r)), fill=STYLE["dec"][0], outline=STYLE["dec"][1],
                 width=cv.s(3))
    cv.text(cx, cy - 78, "0", bold=True)
    cv.text(cx, cy + 12, cv.wrap("Mera's General Merchandise Store Management System with POS and Chat Support",
                                 cv.f, 290), lh=29)

    def entity(x, y, w, h, label):
        cv.box(x - w / 2, y - h / 2, x + w / 2, y + h / 2, "proc", radius=4, width=3)
        cv.text(x, y, cv.wrap(label, cv.fb, w - 24), bold=True)

    def on_circle(angle_deg):
        a = math.radians(angle_deg)
        return (cx + r * math.cos(a), cy + r * math.sin(a))

    # left: customer, right: administrator - one arrow in, one arrow out
    entity(115, cy, 200, 110, "Customer")
    entity(1185, cy, 200, 110, "Administrator")
    off = 62
    x_in = cx - math.sqrt(r * r - off * off)
    cv.arrow((215, cy - off), (x_in, cy - off))
    cv.text((215 + x_in) / 2, cy - off - 62,
            cv.wrap("registration and login details, product searches, inquiries, chat questions, reset requests",
                    cv.f, 255), lh=25)
    cv.arrow((x_in, cy + off), (215, cy + off))
    cv.text((215 + x_in) / 2, cy + off + 62,
            cv.wrap("product catalogue, prices, stock status, chatbot replies, inquiry confirmation", cv.f, 255), lh=25)
    x_in_r = cx + math.sqrt(r * r - off * off)
    cv.arrow((1085, cy - off), (x_in_r, cy - off))
    cv.text((1085 + x_in_r) / 2, cy - off - 62,
            cv.wrap("login, product and user data, POS sales and payments, inquiry replies, report requests",
                    cv.f, 255), lh=25)
    cv.arrow((x_in_r, cy + off), (1085, cy + off))
    cv.text((1085 + x_in_r) / 2, cy + off + 62,
            cv.wrap("dashboard, reports, receipts, customer inquiries, activity logs, low-stock alerts", cv.f, 255),
            lh=25)

    # top: Google Sign-In
    entity(cx, 100, 280, 80, "Google Sign-In")
    gx = 60
    ytop = cy - math.sqrt(r * r - gx * gx)
    cv.arrow((cx - gx, ytop), (cx - gx, 140))
    cv.text(cx - gx - 12, (ytop + 140) / 2, cv.wrap("ID token", cv.f, 150), anchor="rm")
    cv.arrow((cx + gx, 140), (cx + gx, ytop))
    cv.text(cx + gx + 12, (ytop + 140) / 2, cv.wrap("verified name and email", cv.f, 220), anchor="lm")

    # bottom: e-mail and push notification (the system only sends)
    entity(330, 920, 270, 90, "Email Service")
    entity(970, 920, 330, 100, "Push Notification Service")
    for x, label, side in ((330, "password reset link,\ninquiry reply email", -1),
                           (970, "inquiry reply\nnotification", 1)):
        tip = (x, 920 - 45 if x == 330 else 920 - 50)
        ang = math.degrees(math.atan2(tip[1] - cy, tip[0] - cx))
        start = on_circle(ang)
        cv.arrow(start, tip)
        mx, my = (start[0] + tip[0]) / 2, (start[1] + tip[1]) / 2
        cv.text(mx + side * 26, my, label.split("\n"), anchor="rm" if side < 0 else "lm", lh=26)
    return cv.save(path)


# ---------------------------------------------------------------------------------------
# Entity relationship diagram (crow's foot notation)
# ---------------------------------------------------------------------------------------
TABLES = {  # name: (column, attributes) ; "PK"/"FK" prefixes mark keys
    "users": ["PK id", "name", "email", "password", "role", "created_at", "updated_at"],
    "sales": ["PK id", "FK user_id", "total", "created_at", "updated_at"],
    "inquiries": ["PK id", "customer_name", "customer_email", "subject", "message", "fcm_token", "status",
                  "response", "responded_at", "FK responded_by", "created_at", "updated_at"],
    "sale_items": ["PK id", "FK sale_id", "FK product_id", "qty", "price", "created_at", "updated_at"],
    "activity_logs": ["PK id", "FK user_id", "user_name", "user_role", "action", "description", "ip_address",
                      "user_agent", "created_at", "updated_at"],
    "password_reset_tokens": ["PK email", "token", "created_at"],
    "products": ["PK id", "sku", "name", "unit", "category", "price", "bulk_price", "bulk_min_qty", "quantity",
                 "created_at", "updated_at"],
    "messages": ["PK id", "user_name", "message", "created_at", "updated_at"],
}


def erd(path):
    cv = Canvas(1160, 1090)
    bw, hh, rh, gap = 300, 42, 29, 72
    col_x = {"A": 30, "B": 430, "C": 830}
    layout = [  # (table, column, previous table in that column)
        ("sales", "A", None), ("sale_items", "A", "sales"), ("products", "A", "sale_items"),
        ("users", "B", None), ("activity_logs", "B", "users"),
        ("inquiries", "C", None), ("password_reset_tokens", "C", "inquiries"), ("messages", "C", "password_reset_tokens"),
    ]
    pos = {}
    for name, col, prev in layout:
        h = hh + len(TABLES[name]) * rh + 12
        y = 30 if prev is None else pos[prev][1] + pos[prev][3] + gap
        pos[name] = (col_x[col], y, bw, h)

    def sym(pt, direction, kind):
        """Cardinality symbol at a table edge; `direction` points away from the table along the line."""
        dx, dy = direction
        px, py = -dy, dx
        at = lambda t, s=0: (pt[0] + dx * t + px * s, pt[1] + dy * t + py * s)  # noqa: E731
        w = 3
        if kind in ("many0", "many1"):                       # crow's foot
            tip = at(22)
            for sdir in (-11, 0, 11):
                cv.line([tip, at(0, sdir)], width=w)
            if kind == "many0":
                c = at(33)
                cv.d.ellipse((cv.s(c[0] - 6), cv.s(c[1] - 6), cv.s(c[0] + 6), cv.s(c[1] + 6)), fill="#FFFFFF",
                             outline=LINE, width=cv.s(w))
            else:
                cv.line([at(30, -11), at(30, 11)], width=w)
        elif kind == "one":
            cv.line([at(9, -11), at(9, 11)], width=w)
            cv.line([at(19, -11), at(19, 11)], width=w)
        elif kind == "one0":
            cv.line([at(9, -11), at(9, 11)], width=w)
            c = at(24)
            cv.d.ellipse((cv.s(c[0] - 6), cv.s(c[1] - 6), cv.s(c[0] + 6), cv.s(c[1] + 6)), fill="#FFFFFF",
                         outline=LINE, width=cv.s(w))

    # boxes
    for name, (x, y, w, h) in pos.items():
        cv.d.rectangle((cv.s(x), cv.s(y), cv.s(x + w), cv.s(y + h)), fill="#FFFFFF", outline="#222222", width=cv.s(2))
        cv.d.rectangle((cv.s(x), cv.s(y), cv.s(x + w), cv.s(y + hh)), fill="#2F5597", outline="#222222", width=cv.s(2))
        cv.d.text((cv.s(x + w / 2), cv.s(y + hh / 2)), name, font=cv.fb, fill="#FFFFFF", anchor="mm")
        for i, a in enumerate(TABLES[name]):
            ty = y + hh + 6 + i * rh + rh / 2
            key = a.split(" ")[0] if a.startswith(("PK", "FK")) else ""
            if key:
                cv.d.text((cv.s(x + 12), cv.s(ty)), key, font=cv.fb, fill="#2F5597" if key == "PK" else "#C55A11", anchor="lm")
                cv.d.text((cv.s(x + 56), cv.s(ty)), a[3:], font=cv.fb if key == "PK" else cv.f, fill=INK, anchor="lm")
            else:
                cv.d.text((cv.s(x + 56), cv.s(ty)), a, font=cv.f, fill=INK, anchor="lm")
    def rel(p, q, a_kind, b_kind, label, label_at, dash=False, pts=None):
        """Draw a relationship line with its cardinality symbols and a verb label."""
        path_pts = pts or [p, q]
        cv.line(path_pts, dash=dash)
        d0 = (path_pts[1][0] - path_pts[0][0], path_pts[1][1] - path_pts[0][1])
        n0 = math.hypot(*d0) or 1
        sym(path_pts[0], (d0[0] / n0, d0[1] / n0), a_kind)
        d1 = (path_pts[-2][0] - path_pts[-1][0], path_pts[-2][1] - path_pts[-1][1])
        n1 = math.hypot(*d1) or 1
        sym(path_pts[-1], (d1[0] / n1, d1[1] / n1), b_kind)
        cv.text(label_at[0], label_at[1], label.split("\n"), lh=24, bg=True)

    ux, uy, uw, uh = pos["users"]
    sx, sy, sw, sh = pos["sales"]
    ix, iy, iw, ih = pos["inquiries"]
    lx, ly, lw_, lh_ = pos["activity_logs"]
    six, siy, siw, sih = pos["sale_items"]
    px, py, pw, ph = pos["products"]
    tx, ty, tw, th = pos["password_reset_tokens"]
    y1 = uy + 80
    rel((ux, y1), (sx + sw, y1), "one", "many0", "places", ((ux + sx + sw) / 2, y1 - 26))
    rel((ux + uw, y1), (ix, y1), "one0", "many0", "answers", ((ux + uw + ix) / 2, y1 - 26))
    rel((ux + 150, uy + uh), (lx + 150, ly), "one", "many0", "performs", (ux + 232, (uy + uh + ly) / 2))
    rel((sx + 150, sy + sh), (six + 150, siy), "one", "many1", "contains", (sx + 220, (sy + sh + siy) / 2))
    rel((px + 150, py), (six + 150, siy + sih), "one", "many0", "sold in", (px + 218, (py + siy + sih) / 2))
    ymail = uy + 215
    rel(None, None, "one0", "one0", "reset for\n(email)", (ux + uw + 50, ymail + 78), dash=True,
        pts=[(ux + uw, ymail), (ux + uw + 50, ymail), (ux + uw + 50, ty + 36), (tx, ty + 36)])
    mx_, my_, mw_, mh_ = pos["messages"]
    cv.text(mx_ + mw_ / 2, my_ + mh_ + 24, "no foreign key: team chat messages", lh=24)
    return cv.save(path)


def main():
    builders = {"agile": agile, "architecture": architecture, "usecase": usecase, "context": context, "erd": erd}
    for key, no, caption, name in FIGURES:
        size = builders[key](OUT / name)
        print(f"Figure {no} {caption}: {name} {size[0]}x{size[1]} (aspect {size[1] / size[0]:.2f})")


if __name__ == "__main__":
    main()
