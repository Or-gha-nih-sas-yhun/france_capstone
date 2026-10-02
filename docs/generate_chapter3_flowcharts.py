"""
Generates the Chapter III system flowcharts of Mera's General Merchandise Store
Management System as PNG files in docs/chapter-3-diagrams/flowcharts/.

The logic of every chart follows the actual application code (controllers and
views) and the earlier draw.io drafts (meras-detailed-flowcharts.drawio):
data stores D1 Users, D2 Products, D3 Inquiries, D4 Messages, D5 Sales,
D6 Sale Items, D7 Activity Logs, D8 Password Reset Tokens.

Run:  python docs/generate_chapter3_flowcharts.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from flowchart_engine import Chart  # noqa: E402

OUT = Path(__file__).resolve().parent / "chapter-3-diagrams" / "flowcharts"
FIRST_FIGURE = 18  # Figure 18 is the first flowchart in Chapter III

KEYS = ["overall", "register", "login", "recovery", "browse", "chat",
        "inventory", "pos", "inquiry", "dashboard", "admin"]


def F(key):
    return FIRST_FIGURE + KEYS.index(key)


def overall():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("open", "proc", "Open the MERAS website or the Android application", 1, 1)
    c.n("has", "dec", "Has an account?", 1, 2)
    c.n("reg", "proc", f"Register a customer account (Figure {F('register')})", 2, 2)
    c.n("login", "proc", f"Log in with email and password (Figure {F('login')})", 1, 3)
    c.n("valid", "dec", "Credentials valid?", 1, 4)
    c.n("err", "proc", f"Show an error; the user retries or recovers the password "
                       f"(Figure {F('recovery')})", 0, 4)
    c.n("role", "dec", "Role is administrator?", 1, 5)
    c.n("adm", "ref", f"Admin operations: inventory, POS, inquiries, dashboard, reports, users, "
                      f"logs, and settings (Figures {F('inventory')} to {F('admin')})", 0, 6)
    c.n("cus", "ref", f"Customer operations: browse products, send inquiries, and use the chat "
                      f"(Figures {F('browse')} and {F('chat')})", 1, 6)
    c.n("out", "proc", "Log out and end the session", 1, 7)
    c.n("end", "term", "End", 1, 8)
    c.e("start", "open")
    c.e("open", "has")
    c.e("has", "login", "Yes")
    c.e("has", "reg", "No", out="r", into="l")
    c.e("login", "valid")
    c.e("valid", "err", "No", out="l", into="r")
    c.e("err", "login", out="t", into="l")
    c.e("valid", "role", "Yes")
    c.e("reg", "role", out="b", into="r")
    c.e("role", "adm", "Yes", out="l", into="t")
    c.e("role", "cus", "No")
    c.e("adm", "out", out="b", into="l")
    c.e("cus", "out")
    c.e("out", "end")
    return c


def register():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("open", "proc", "Open the registration page of the website or the Android application", 1, 1)
    c.n("form", "proc", "Fill in the registration form: full name, email address, password, "
                        "and password confirmation", 1, 2)
    c.n("val", "proc", "Validate the required fields, the email format, the email uniqueness, "
                       "and the password length (at least 6 characters)", 1, 3)
    c.n("ok", "dec", "Data valid?", 1, 4)
    c.n("err", "proc", "Show the validation errors and ask the user to correct them", 2, 4)
    c.n("save", "proc", "Create the customer account and save the password in hashed form", 1, 5)
    c.n("d1", "store", "D1 Users", 0, 5)
    c.n("sess", "proc", "Log the customer in automatically and regenerate the session", 1, 6)
    c.n("log", "proc", "Record the registration in the activity log (D7)", 1, 7)
    c.n("go", "ref", f"Continue to the customer home page (Figure {F('browse')})", 1, 8)
    c.e("start", "open")
    c.e("open", "form")
    c.e("form", "val")
    c.e("val", "ok")
    c.e("ok", "err", "No", out="r", into="l")
    c.e("err", "form", out="t", into="r")
    c.e("ok", "save", "Yes")
    c.link("save", "d1")
    c.e("save", "sess")
    c.e("sess", "log")
    c.e("log", "go")
    return c


def login():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("open", "proc", "Open the login page of the website or the Android application", 1, 1)
    c.n("form", "proc", "Enter the email address and password, or choose Sign in with Google", 1, 2)
    c.n("chk", "proc", "Validate the input and check the credentials against the user records", 1, 3)
    c.n("d1", "store", "D1 Users", 0, 3)
    c.n("ok", "dec", "Credentials valid?", 1, 4)
    c.n("err", "proc", f"Show an error message; the user may retry or select Forgot Password "
                       f"(Figure {F('recovery')})", 2, 4)
    c.n("mob", "dec", "Staff account used in the mobile app?", 1, 5, w=400)
    c.n("deny", "proc", "Deny access and log out; staff must use the web portal", 2, 5)
    c.n("sess", "proc", "Regenerate the session and record the login in the activity log (D7)", 1, 6)
    c.n("role", "dec", "Role is administrator?", 1, 7)
    c.n("adm", "ref", f"Admin portal: settings page and admin modules (Figures {F('inventory')} "
                      f"to {F('admin')})", 0, 8)
    c.n("cus", "ref", f"Customer home page and product catalogue (Figure {F('browse')})", 2, 8)
    c.e("start", "open")
    c.e("open", "form")
    c.e("form", "chk")
    c.link("chk", "d1")
    c.e("chk", "ok")
    c.e("ok", "err", "No", out="r", into="l")
    c.e("err", "form", out="t", into="r")
    c.e("ok", "mob", "Yes")
    c.e("mob", "deny", "Yes", out="r", into="l")
    c.e("deny", "form", out="r", into="r", lane="R")
    c.e("mob", "sess", "No")
    c.e("sess", "role")
    c.e("role", "adm", "Yes", out="b", into="t", at="end")
    c.e("role", "cus", "No", out="b", into="t", at="end")
    return c


def recovery():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("req", "proc", "Open the Forgot Password page and enter the registered email address", 1, 1)
    c.n("ex", "dec", "Email address registered?", 1, 2)
    c.n("nf", "proc", "Show the message that no account was found", 2, 2)
    c.n("tok", "proc", "Generate a random token and save it in hashed form", 1, 3)
    c.n("d8", "store", "D8 Password Reset Tokens", 0, 3, w=250)
    c.n("mail", "proc", "Send the reset link to the email address (valid for 60 minutes)", 1, 4)
    c.n("open", "proc", "The user opens the link and enters a new password with confirmation", 1, 5)
    c.n("chk", "dec", "Link valid and password accepted?", 1, 6)
    c.n("bad", "proc", "Show that the link is invalid or expired and ask the user to request a new link", 2, 6)
    c.n("upd", "proc", "Update the password hash and delete the used token", 1, 7)
    c.n("d1", "store", "D1 Users", 0, 7)
    c.n("ok", "proc", "Show the success message and return to the login page", 1, 8)
    c.n("end", "term", "End", 1, 9)
    c.e("start", "req")
    c.e("req", "ex")
    c.e("ex", "nf", "No", out="r", into="l")
    c.e("nf", "req", out="t", into="r")
    c.e("ex", "tok", "Yes")
    c.link("tok", "d8")
    c.e("tok", "mail")
    c.e("mail", "open")
    c.e("open", "chk")
    c.e("chk", "bad", "No", out="r", into="l")
    c.e("bad", "req", out="r", into="r", lane="R")
    c.e("chk", "upd", "Yes")
    c.link("upd", "d1")
    c.e("upd", "ok")
    c.e("ok", "end")
    return c


def browse():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("home", "proc", "Open the store home page and load the product catalogue with prices "
                        "and stock status", 1, 1)
    c.n("d2", "store", "D2 Products", 0, 1)
    c.n("srch", "proc", "Search by name or SKU, filter by category, and switch between the "
                        "grid and table views", 1, 2)
    c.n("ask", "dec", "Send an inquiry?", 1, 3)
    c.n("fin", "term", "End", 2, 3)
    c.n("form", "proc", "Fill in the inquiry form: name, email, subject, and message", 1, 4)
    c.n("ok", "dec", "Details valid?", 1, 5)
    c.n("err", "proc", "Show the errors and ask the user to correct them", 2, 5)
    c.n("save", "proc", "Save the pending inquiry with the device token and record the activity "
                        "(D7)", 1, 6)
    c.n("d3", "store", "D3 Inquiries", 0, 6)
    c.n("conf", "proc", "Show the confirmation that the inquiry was submitted", 1, 7)
    c.n("read", "proc", "Receive the store's reply by email or push notification and read it "
                        "under Profile or Notifications", 1, 8)
    c.n("end", "term", "End", 1, 9)
    c.e("start", "home")
    c.link("home", "d2")
    c.e("home", "srch")
    c.e("srch", "ask")
    c.e("ask", "fin", "No", out="r", into="l")
    c.e("ask", "form", "Yes")
    c.e("form", "ok")
    c.e("ok", "err", "No", out="r", into="l")
    c.e("err", "form", out="t", into="r")
    c.e("ok", "save", "Yes")
    c.link("save", "d3")
    c.e("save", "conf")
    c.e("conf", "read")
    c.e("read", "end")
    return c


def chat():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("open", "proc", "Open the chat widget of the website or the Android application", 1, 1)
    c.n("send", "proc", "Type a question or choose a quick button: Store Hours, Location, "
                        "Products, or Payment", 1, 2)
    c.n("save", "proc", "Send the question to the chatbot and save the message if the customer "
                        "is logged in", 1, 3)
    c.n("d4", "store", "D4 Messages", 0, 3)
    c.n("intent", "proc", "Determine the intent: greeting, hours, location, payment, products, "
                          "or live help", 1, 4)
    c.n("d2", "store", "D2 Products", 2, 4)
    c.n("prod", "dec", "Product-related?", 1, 5)
    c.n("srch", "proc", "Search the matching products and their stock", 2, 5)
    c.n("reply", "proc", "Return the chatbot reply, suggestions, and product cards", 1, 6)
    c.n("live", "dec", "Live help needed?", 1, 7)
    c.n("msgr", "proc", "Offer the Facebook Messenger link for a live conversation", 2, 7)
    c.n("more", "dec", "Ask another question?", 1, 8)
    c.n("end", "term", "End", 1, 9)
    c.e("start", "open")
    c.e("open", "send")
    c.e("send", "save")
    c.link("save", "d4")
    c.e("save", "intent")
    c.link("srch", "d2", out="t", into="b")
    c.e("intent", "prod")
    c.e("prod", "srch", "Yes", out="r", into="l")
    c.e("prod", "reply", "No")
    c.e("srch", "reply", out="b", into="r")
    c.e("reply", "live")
    c.e("live", "msgr", "Yes", out="r", into="l")
    c.e("live", "more", "No")
    c.e("msgr", "more", out="b", into="r")
    c.e("more", "send", "Yes", out="l", into="l", lane="L")
    c.e("more", "end", "No")
    return c


def inventory():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("list", "proc", "The administrator opens Inventory Management and views or searches the "
                        "product list", 1, 1)
    c.n("d2", "store", "D2 Products", 0, 1)
    c.n("act", "dec", "Inventory action?", 1, 2)
    c.n("form", "proc", "Fill in the product form: name, SKU, unit, price, stock, and optional "
                        "bulk price", 0, 3)
    c.n("ok", "dec", "Data valid?", 0, 4, w=300)
    c.n("save", "proc", "Insert the new product or update the existing product", 0, 5)
    c.n("up", "proc", "Upload a CSV file with the product name, price, quantity, and unit", 1, 3)
    c.n("vf", "proc", "Validate the file (.csv, up to 2 MB) and each row; rows with errors are "
                      "listed", 1, 4)
    c.n("imp", "proc", "Add new products with generated SKUs and update existing products by name", 1, 5)
    c.n("conf", "proc", "Select the product and confirm the deletion", 2, 3)
    c.n("del", "proc", "Delete the product record", 2, 4)
    c.n("log", "proc", "Record the action in the activity log (D7) and show the result message", 1, 6)
    c.n("more", "dec", "Another action?", 1, 7)
    c.n("end", "term", "End", 1, 8)
    c.e("start", "list")
    c.link("list", "d2")
    c.e("list", "act")
    c.e("act", "form", "Add / edit", out="l", into="t")
    c.e("act", "up", "Import", out="b", into="t")
    c.e("act", "conf", "Delete", out="r", into="t")
    c.e("form", "ok")
    c.e("ok", "form", "No", out="l", into="l", lane="L")
    c.e("ok", "save", "Yes")
    c.e("up", "vf")
    c.e("vf", "imp")
    c.e("conf", "del")
    c.e("save", "log", out="b", into="l")
    c.e("imp", "log")
    c.e("del", "log", out="b", into="r")
    c.e("log", "more")
    c.e("more", "list", "Yes", out="r", into="r", lane="R")
    c.e("more", "end", "No")
    return c


def pos():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("srch", "proc", "The administrator opens the POS screen, searches the catalogue, and "
                        "selects a product and quantity", 1, 1)
    c.n("stock", "dec", "Stock available?", 1, 2)
    c.n("oos", "proc", "Show the message that the product is out of stock", 0, 2)
    c.n("add", "proc", "Add the item to the cart (not more than the available stock) and apply "
                       "the bulk price when the bulk quantity is reached", 1, 3)
    c.n("d2", "store", "D2 Products", 2, 3)
    c.n("more", "dec", "Add more items?", 1, 4)
    c.n("pay", "dec", "Payment method is GCash?", 1, 5)
    c.n("gc", "proc", "Show the GCash QR code and confirm that the payment is received", 2, 5)
    c.n("cash", "dec", "Cash tendered enough for the total?", 1, 6, w=400)
    c.n("short", "proc", "Show the message that the cash is insufficient", 0, 6)
    c.n("sale", "proc", "Save the sale (D5) and its items (D6) in one database transaction and "
                        "decrease the product stock (D2)", 1, 7)
    c.n("rec", "proc", "Record the sale in the activity log (D7), compute the change, and show "
                       "the printable receipt", 1, 8)
    c.n("end", "term", "End", 1, 9)
    c.e("start", "srch")
    c.e("srch", "stock")
    c.e("stock", "oos", "No", out="l", into="r")
    c.e("oos", "srch", out="t", into="l")
    c.e("stock", "add", "Yes")
    c.link("add", "d2", out="r", into="l")
    c.e("add", "more")
    c.e("more", "srch", "Yes", out="r", into="r", lane="R")
    c.e("more", "pay", "No")
    c.e("pay", "gc", "Yes", out="r", into="l")
    c.e("pay", "cash", "No (cash)")
    c.e("cash", "short", "No", out="l", into="r")
    c.e("short", "pay", out="t", into="l")
    c.e("cash", "sale", "Yes")
    c.e("gc", "sale", out="b", into="r")
    c.e("sale", "rec")
    c.e("rec", "end")
    return c


def inquiry():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("list", "proc", "The administrator opens Customer Inquiries and views the history; "
                        "search or filter by status (Pending or Responded)", 1, 1)
    c.n("d3", "store", "D3 Inquiries", 0, 1)
    c.n("act", "dec", "Inquiry action?", 1, 2)
    c.n("write", "proc", "Write the response to the customer", 0, 3)
    c.n("save", "proc", "Save the response, mark the inquiry as Responded, and store the "
                        "responding administrator", 0, 4)
    c.n("send", "proc", "Send the reply by email, and by push notification when the customer's "
                        "device token is saved", 0, 5)
    c.n("tog", "proc", "Switch the status between Pending and Responded", 1, 3)
    c.n("upd", "proc", "Update the status of the inquiry", 1, 4)
    c.n("conf", "proc", "Select the inquiry and confirm the deletion", 2, 3)
    c.n("del", "proc", "Delete the inquiry record", 2, 4)
    c.n("log", "proc", "Record the action in the activity log (D7) and show the result message", 1, 6)
    c.n("end", "term", "End", 1, 7)
    c.e("start", "list")
    c.link("list", "d3", out="l", into="r")
    c.e("list", "act")
    c.e("act", "write", "Respond", out="l", into="t")
    c.e("act", "tog", "Status", out="b", into="t")
    c.e("act", "conf", "Delete", out="r", into="t")
    c.e("write", "save")
    c.e("save", "send")
    c.e("tog", "upd")
    c.e("conf", "del")
    c.e("send", "log", out="b", into="l")
    c.e("upd", "log")
    c.e("del", "log", out="b", into="r")
    c.e("log", "end")
    return c


def dashboard():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("open", "proc", "The administrator opens the Dashboard or Sales Reports", 1, 1)
    c.n("pick", "dec", "Which module?", 1, 2)
    c.n("d1", "proc", "Read the daily, weekly, and monthly sales, the total revenue, and the "
                      "product count (D5, D2)", 0, 3)
    c.n("d2", "proc", "Prepare the seven-day sales trend, top-selling products, and stock by "
                      "category charts", 0, 4)
    c.n("d3", "proc", "Display the indicators, charts, recent sales, and low-stock alerts "
                      "(stock below 5)", 0, 5)
    c.n("r1", "proc", "Select the period: weekly, monthly, yearly, or all records", 2, 3)
    c.n("r2", "proc", "Compute the revenue, number of transactions, average sale, top-selling "
                      "products, and recent transactions (D5, D6, D2)", 2, 4)
    c.n("r3", "proc", "Display the sales report", 2, 5)
    c.n("ex", "dec", "Export or print?", 2, 6, w=300)
    c.n("dl", "proc", "Download the CSV file or print the report", 1, 6)
    c.n("mrg", "proc", "The administrator opens another module or logs out", 1, 7)
    c.n("end", "term", "End", 1, 8)
    c.e("start", "open")
    c.e("open", "pick")
    c.e("pick", "d1", "Dashboard", out="l", into="t")
    c.e("pick", "r1", "Reports", out="r", into="t")
    c.e("d1", "d2")
    c.e("d2", "d3")
    c.e("r1", "r2")
    c.e("r2", "r3")
    c.e("r3", "ex")
    c.e("ex", "dl", "Yes", out="l", into="r")
    c.e("ex", "mrg", "No", out="b", into="r")
    c.e("dl", "mrg")
    c.e("d3", "mrg", out="b", into="l")
    c.e("mrg", "end")
    return c


def admin():
    c = Chart()
    c.n("start", "term", "Start", 1, 0)
    c.n("open", "proc", "The administrator opens User Accounts, Activity Logs, or System "
                        "Settings", 1, 1)
    c.n("pick", "dec", "Which module?", 1, 2)
    c.n("u1", "proc", "View and search the user accounts (D1 Users)", 0, 3)
    c.n("u2", "proc", "Create an account, change a role, or delete an account (only one "
                      "administrator; own account protected)", 0, 4)
    c.n("u3", "proc", "Save the change in D1 Users and record it in the activity log", 0, 5)
    c.n("l1", "proc", "View the system activity logs (D7)", 1, 3)
    c.n("l2", "proc", "Filter by role (admin, customer, guest) or search by user, action, "
                      "details, or IP address", 1, 4)
    c.n("l3", "proc", "Display the time, user, role, action, details, IP address, and browser", 1, 5)
    c.n("s1", "proc", "View the system overview: total revenue, sales count, and active users", 2, 3)
    c.n("s2", "proc", "Export and back up the records to CSV, change the administrator password, "
                      "or run a maintenance action", 2, 4)
    c.n("s3", "proc", "Apply the action (seed demo products, reset sales, or clear chat) and "
                      "record it in the activity log", 2, 5)
    c.n("mrg", "proc", "Return to the admin menu or log out", 1, 6)
    c.n("end", "term", "End", 1, 7)
    c.e("start", "open")
    c.e("open", "pick")
    c.e("pick", "u1", "Users", out="l", into="t")
    c.e("pick", "l1", "Logs", out="b", into="t")
    c.e("pick", "s1", "Settings", out="r", into="t")
    c.e("u1", "u2")
    c.e("u2", "u3")
    c.e("l1", "l2")
    c.e("l2", "l3")
    c.e("s1", "s2")
    c.e("s2", "s3")
    c.e("u3", "mrg", out="b", into="l")
    c.e("l3", "mrg")
    c.e("s3", "mrg", out="b", into="r")
    c.e("mrg", "end")
    return c


BUILDERS = {
    "overall": overall, "register": register, "login": login, "recovery": recovery,
    "browse": browse, "chat": chat, "inventory": inventory, "pos": pos, "inquiry": inquiry,
    "dashboard": dashboard, "admin": admin,
}

# (key, caption used under the figure in Chapter III)
CAPTIONS = {
    "overall": "Overall System Flowchart",
    "register": "Customer Registration Flowchart",
    "login": "Login Flowchart",
    "recovery": "Password Recovery Flowchart",
    "browse": "Customer Product Browsing and Inquiry Flowchart",
    "chat": "Customer Chat Support Flowchart",
    "inventory": "Inventory Management Flowchart",
    "pos": "POS Transaction Flowchart",
    "inquiry": "Inquiry Management Flowchart",
    "dashboard": "Dashboard and Sales Reports Flowchart",
    "admin": "User Accounts, Activity Logs, and System Settings Flowchart",
}


def filename(key):
    return f"fc-{KEYS.index(key) + 1:02d}-{key}.png"


def main(only=None):
    for key in KEYS:
        if only and key not in only:
            continue
        size = BUILDERS[key]().render(OUT / filename(key))
        print(f"Figure {F(key)} {key}: {filename(key)} {size[0]}x{size[1]} (aspect {size[1] / size[0]:.2f})")


if __name__ == "__main__":
    main(sys.argv[1:] or None)
