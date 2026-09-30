from math import atan2, cos, sin, pi
from pathlib import Path
import textwrap

from PIL import Image, ImageDraw, ImageFont


OUTPUT_DIR = Path(__file__).with_name("chapter-3-diagrams")
WIDTH = 2000
HEIGHT = 1300
INK = "#1F2937"
BLUE = "#DCEBFA"
GREEN = "#E0F2E9"
YELLOW = "#FFF3CD"
PURPLE = "#EEE6FF"
RED = "#FBE4E4"
GRAY = "#F5F7FA"


def font(size, bold=False):
    name = "arialbd.ttf" if bold else "arial.ttf"
    return ImageFont.truetype(Path("C:/Windows/Fonts") / name, size)


TITLE_FONT = font(40, True)
SUBTITLE_FONT = font(24)
BOX_FONT = font(25, True)
BODY_FONT = font(20)
SMALL_FONT = font(17)


def canvas(title, subtitle=""):
    image = Image.new("RGB", (WIDTH, HEIGHT), "#FFFFFF")
    draw = ImageDraw.Draw(image)
    draw.rectangle((0, 0, WIDTH, 115), fill="#F0F6FC")
    draw.text((WIDTH // 2, 32), title, font=TITLE_FONT, fill=INK, anchor="ma")
    if subtitle:
        draw.text((WIDTH // 2, 82), subtitle, font=SUBTITLE_FONT, fill="#4B5563", anchor="ma")
    return image, draw


def wrapped_lines(value, width, text_font):
    average_width = max(1, int(text_font.size * 0.55))
    return textwrap.wrap(value, width=max(8, width // average_width)) or [value]


def centered_text(draw, rectangle, value, text_font=BODY_FONT, fill=INK, spacing=5):
    x1, y1, x2, y2 = rectangle
    lines = []
    for paragraph in value.split("\n"):
        lines.extend(wrapped_lines(paragraph, x2 - x1 - 28, text_font))
    heights = [draw.textbbox((0, 0), line, font=text_font)[3] for line in lines]
    total_height = sum(heights) + spacing * (len(lines) - 1)
    y = (y1 + y2 - total_height) / 2
    for line, line_height in zip(lines, heights):
        draw.text(((x1 + x2) / 2, y), line, font=text_font, fill=fill, anchor="ma")
        y += line_height + spacing


def box(draw, rectangle, value, fill=BLUE, text_font=BOX_FONT, radius=22):
    draw.rounded_rectangle(rectangle, radius=radius, fill=fill, outline=INK, width=3)
    centered_text(draw, rectangle, value, text_font)


def datastore(draw, rectangle, value):
    x1, y1, x2, y2 = rectangle
    draw.rectangle(rectangle, fill="#FFFFFF", outline=INK, width=3)
    draw.arc((x1, y1 - 14, x2, y1 + 14), 0, 180, fill=INK, width=3)
    draw.arc((x1, y2 - 14, x2, y2 + 14), 0, 180, fill=INK, width=3)
    centered_text(draw, rectangle, value, SMALL_FONT)


def process(draw, rectangle, value, fill=GREEN):
    draw.ellipse(rectangle, fill=fill, outline=INK, width=3)
    centered_text(draw, rectangle, value, BODY_FONT)


def arrow(draw, start, end, label="", dashed=False):
    x1, y1 = start
    x2, y2 = end
    angle = atan2(y2 - y1, x2 - x1)
    if dashed:
        length = ((x2 - x1) ** 2 + (y2 - y1) ** 2) ** 0.5
        for offset in range(0, int(length), 20):
            segment_end = min(offset + 11, length)
            draw.line(
                (
                    x1 + cos(angle) * offset,
                    y1 + sin(angle) * offset,
                    x1 + cos(angle) * segment_end,
                    y1 + sin(angle) * segment_end,
                ),
                fill=INK,
                width=3,
            )
    else:
        draw.line((x1, y1, x2, y2), fill=INK, width=3)
    head = 16
    points = [
        (x2, y2),
        (x2 - head * cos(angle - pi / 6), y2 - head * sin(angle - pi / 6)),
        (x2 - head * cos(angle + pi / 6), y2 - head * sin(angle + pi / 6)),
    ]
    draw.polygon(points, fill=INK)
    if label:
        midpoint = ((x1 + x2) / 2, (y1 + y2) / 2)
        draw.rounded_rectangle((midpoint[0] - 100, midpoint[1] - 20, midpoint[0] + 100, midpoint[1] + 20), 8, fill="#FFFFFF")
        draw.text(midpoint, label, font=SMALL_FONT, fill=INK, anchor="mm")


def caption(draw, value):
    draw.text((WIDTH // 2, HEIGHT - 35), value, font=SMALL_FONT, fill="#4B5563", anchor="ms")


def save(image, name):
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    image.save(OUTPUT_DIR / name, dpi=(220, 220))


def rad_lifecycle():
    image, draw = canvas("Rapid Application Development (RAD) Lifecycle", "Rapid prototyping and user feedback guide the development process")
    phases = [
        ("1. Requirements\nPlanning", BLUE),
        ("2. User\nDesign", GREEN),
        ("3. Rapid\nConstruction", YELLOW),
        ("4. Cutover", PURPLE),
    ]
    x = 330
    for index, (label, colour) in enumerate(phases):
        rectangle = (x, 470, x + 310, 650)
        box(draw, rectangle, label, colour)
        if index < len(phases) - 1:
            arrow(draw, (x + 310, 560), (x + 390, 560))
        x += 390
    draw.arc((100, 220, 1890, 1050), 190, 350, fill="#2563EB", width=6)
    arrow(draw, (470, 930), (400, 850), "User feedback", dashed=True)
    draw.text((WIDTH // 2, 290), "Working prototypes are reviewed and refined before final deployment.", font=BODY_FONT, fill=INK, anchor="ma")
    caption(draw, "Figure 1. Rapid Application Development (RAD) Lifecycle Model")
    save(image, "figure-01-rad-lifecycle.png")


def gantt_chart():
    image, draw = canvas("Project Development Schedule", "Indicative 12-week schedule")
    tasks = [
        ("Requirements Planning", 1, 2, BLUE),
        ("System Analysis", 2, 3, GREEN),
        ("Interface and Database Design", 3, 5, YELLOW),
        ("Core Development", 5, 8, PURPLE),
        ("Integration and Testing", 8, 10, RED),
        ("Review, Deployment and Evaluation", 10, 12, BLUE),
    ]
    left = 430
    cell_width = 120
    top = 240
    row_height = 120
    draw.text((120, 190), "Activity", font=BOX_FONT, fill=INK)
    for week in range(1, 13):
        x1 = left + (week - 1) * cell_width
        draw.rectangle((x1, top - 55, x1 + cell_width, top), fill="#F0F6FC", outline=INK, width=2)
        draw.text((x1 + cell_width / 2, top - 28), f"W{week}", font=BODY_FONT, fill=INK, anchor="mm")
    for row, (task, start, end, colour) in enumerate(tasks):
        y1 = top + row * row_height
        draw.rectangle((80, y1, left, y1 + row_height), fill="#F9FAFB", outline=INK, width=2)
        draw.text((105, y1 + row_height / 2), task, font=BODY_FONT, fill=INK, anchor="lm")
        for week in range(1, 13):
            x1 = left + (week - 1) * cell_width
            draw.rectangle((x1, y1, x1 + cell_width, y1 + row_height), outline="#B8C1CC", width=1)
        bar_x1 = left + (start - 1) * cell_width + 10
        bar_x2 = left + end * cell_width - 10
        draw.rounded_rectangle((bar_x1, y1 + 28, bar_x2, y1 + 92), 14, fill=colour, outline=INK, width=2)
    caption(draw, "Figure 2. Gantt Chart of the Mera's General Merchandise Store Management System Development")
    save(image, "figure-02-gantt-chart.png")


def context_diagram():
    image, draw = canvas("Context Flow Diagram", "High-level interaction between external entities and the system")
    box(draw, (730, 440, 1270, 760), "0.0\nMERA'S GENERAL MERCHANDISE\nSTORE MANAGEMENT SYSTEM", GREEN)
    entities = [
        ((90, 210, 460, 390), "Public Visitor\nCatalogue and inquiry request", BLUE, (460, 340), (730, 500), "catalogue / inquiry"),
        ((90, 830, 460, 1010), "Registered Customer\nAccount, support and notifications", PURPLE, (460, 890), (730, 680), "account / message"),
        ((1540, 220, 1910, 400), "Administrator / Staff\nInventory, POS, reports and support", YELLOW, (1540, 350), (1270, 500), "management data"),
        ((1540, 780, 1910, 960), "Email and FCM Services\nResponse and push notifications", RED, (1270, 690), (1540, 840), "notification request"),
    ]
    for rectangle, label, colour, start, end, flow in entities:
        box(draw, rectangle, label, colour)
        arrow(draw, start, end, flow)
        arrow(draw, end, start, "response")
    caption(draw, "Figure 3. Context Flow Diagram")
    save(image, "figure-03-context-flow-diagram.png")


def level_one_dfd():
    image, draw = canvas("Level 1 Data Flow Diagram", "Core processes and primary data stores")
    box(draw, (75, 470, 310, 650), "Customer", BLUE)
    box(draw, (1690, 470, 1925, 650), "Administrator", YELLOW)
    processes = [
        ((385, 230, 675, 390), "1.0\nAccount and Customer Access", GREEN),
        ((855, 230, 1145, 390), "2.0\nInventory Management", GREEN),
        ((1325, 230, 1615, 390), "3.0\nPoint of Sale", GREEN),
        ((385, 750, 675, 910), "4.0\nCustomer Support", PURPLE),
        ((855, 750, 1145, 910), "5.0\nReports and Administration", PURPLE),
    ]
    for rectangle, label, colour in processes:
        process(draw, rectangle, label, colour)
    stores = [
        ((350, 1030, 650, 1140), "D1 Users / Sessions"),
        ((710, 1030, 1010, 1140), "D2 Products"),
        ((1070, 1030, 1370, 1140), "D3 Sales / Sale Items"),
        ((1430, 1030, 1730, 1140), "D4 Inquiries / Logs"),
    ]
    for rectangle, label in stores:
        datastore(draw, rectangle, label)
    for target in [(385, 310), (385, 830)]:
        arrow(draw, (310, 550), target)
    for source in [(1145, 310), (1145, 830), (1615, 310)]:
        arrow(draw, source, (1690, 550))
    connections = [
        ((530, 390), (500, 1030)), ((1000, 390), (860, 1030)), ((1470, 390), (1220, 1030)),
        ((530, 910), (1580, 1030)), ((1000, 910), (1220, 1030)), ((1000, 910), (1580, 1030)),
    ]
    for start, end in connections:
        arrow(draw, start, end)
    caption(draw, "Figure 4. Level 1 Data Flow Diagram")
    save(image, "figure-04-level-1-dfd.png")


def support_dfd():
    image, draw = canvas("Level 2 Data Flow Diagram – Customer Support and Inquiry", "Inquiry, chatbot, response and notification workflow")
    box(draw, (70, 480, 330, 650), "Customer", BLUE)
    process(draw, (470, 300, 770, 470), "4.1\nSubmit Inquiry or Chat Message", GREEN)
    process(draw, (930, 300, 1230, 470), "4.2\nStore Inquiry and Chat History", GREEN)
    process(draw, (1370, 300, 1670, 470), "4.3\nReview and Respond", GREEN)
    process(draw, (930, 720, 1230, 890), "4.4\nChatbot Product and Store Guidance", PURPLE)
    datastore(draw, (900, 1030, 1260, 1140), "D4 Inquiries / Messages")
    datastore(draw, (1400, 1030, 1760, 1140), "D2 Products")
    box(draw, (1660, 720, 1930, 890), "Email / FCM\nNotification Service", RED)
    arrow(draw, (330, 560), (470, 380), "inquiry / message")
    arrow(draw, (770, 385), (930, 385), "validated request")
    arrow(draw, (1230, 385), (1370, 385), "pending inquiry")
    arrow(draw, (1670, 385), (1930, 560), "response")
    arrow(draw, (1080, 470), (1080, 1030), "store")
    arrow(draw, (1370, 805), (1260, 1080), "chat history")
    arrow(draw, (1400, 1080), (1230, 805), "stock / product data")
    arrow(draw, (330, 610), (930, 805), "chatbot question")
    arrow(draw, (930, 805), (330, 610), "guidance")
    caption(draw, "Figure 5. Level 2 Data Flow Diagram – Customer Support and Inquiry Process")
    save(image, "figure-05-customer-support-dfd.png")


def inventory_dfd():
    image, draw = canvas("Level 2 Data Flow Diagram – Inventory Management", "Product maintenance, CSV import and catalogue availability workflow")
    box(draw, (80, 500, 340, 680), "Administrator", YELLOW)
    process(draw, (480, 260, 790, 430), "2.1\nCreate or Update Product", GREEN)
    process(draw, (930, 260, 1240, 430), "2.2\nValidate Product and Unit Data", GREEN)
    process(draw, (1380, 260, 1690, 430), "2.3\nImport Product CSV", GREEN)
    process(draw, (700, 720, 1010, 890), "2.4\nPublish Searchable Catalogue", PURPLE)
    datastore(draw, (990, 1030, 1350, 1140), "D2 Products")
    box(draw, (1660, 720, 1930, 890), "Customer / Chatbot\nCatalogue Access", BLUE)
    datastore(draw, (270, 1030, 630, 1140), "D4 Activity Logs")
    arrow(draw, (340, 560), (480, 345), "product details")
    arrow(draw, (790, 345), (930, 345), "product record")
    arrow(draw, (1240, 345), (990, 1080), "create / update")
    arrow(draw, (340, 620), (1380, 345), "CSV file")
    arrow(draw, (1690, 345), (1350, 1080), "imported rows")
    arrow(draw, (1170, 1030), (855, 890), "product data")
    arrow(draw, (1010, 805), (1660, 805), "catalogue results")
    arrow(draw, (480, 400), (450, 1030), "audit action")
    caption(draw, "Figure 6. Level 2 Data Flow Diagram – Inventory Management Process")
    save(image, "figure-06-inventory-dfd.png")


def pos_dfd():
    image, draw = canvas("Level 2 Data Flow Diagram – Point-of-Sale Transactions", "Cart, payment validation, sale recording, stock update and receipt workflow")
    box(draw, (80, 490, 340, 670), "Administrator / Cashier", YELLOW)
    process(draw, (470, 260, 760, 430), "3.1\nSearch Products and Build Cart", GREEN)
    process(draw, (900, 260, 1190, 430), "3.2\nValidate Payment and Checkout", GREEN)
    process(draw, (1330, 260, 1620, 430), "3.3\nCreate Sale and Sale Items", GREEN)
    process(draw, (900, 720, 1190, 890), "3.4\nUpdate Stock and Prepare Receipt", PURPLE)
    datastore(draw, (430, 1030, 730, 1140), "D2 Products")
    datastore(draw, (840, 1030, 1140, 1140), "D3 Sales")
    datastore(draw, (1250, 1030, 1550, 1140), "D3 Sale Items")
    datastore(draw, (1660, 1030, 1960, 1140), "D4 Activity Logs")
    box(draw, (1660, 720, 1930, 890), "Receipt Output", BLUE)
    arrow(draw, (340, 580), (470, 345), "cart request")
    arrow(draw, (730, 1030), (615, 430), "stock / prices")
    arrow(draw, (760, 345), (900, 345), "cart total")
    arrow(draw, (1190, 345), (1330, 345), "validated payment")
    arrow(draw, (1475, 430), (990, 1030), "sale header")
    arrow(draw, (1475, 430), (1400, 1030), "sale items")
    arrow(draw, (1475, 430), (1045, 720), "sold quantities")
    arrow(draw, (900, 805), (730, 1080), "decrement stock")
    arrow(draw, (1190, 805), (1660, 805), "receipt details")
    arrow(draw, (1190, 850), (1660, 1080), "audit action")
    caption(draw, "Figure 7. Level 2 Data Flow Diagram – Point-of-Sale Process")
    save(image, "figure-07-pos-dfd.png")


def system_architecture():
    image, draw = canvas("System Architecture", "Client-server architecture for store operations and customer support")
    box(draw, (100, 240, 480, 430), "Web Browser\nCustomer and Administrator Access", BLUE)
    box(draw, (100, 650, 480, 840), "Android Application\nCustomer WebView and Notifications", PURPLE)
    box(draw, (730, 290, 1270, 760), "Laravel Web Application\n\nAuthentication and RBAC\nInventory and POS\nSales Reports\nInquiries and Chatbot\nActivity Logs and Settings", GREEN)
    datastore(draw, (1480, 310, 1850, 490), "MySQL Database\nUsers, Products, Sales, Inquiries, Messages and Logs")
    box(draw, (1480, 700, 1850, 890), "External Services\nSMTP Email\nFirebase Cloud Messaging\nMessenger Handoff", RED)
    arrow(draw, (480, 335), (730, 420), "HTTPS requests")
    arrow(draw, (480, 745), (730, 630), "WebView requests")
    arrow(draw, (1270, 430), (1480, 400), "data read / write")
    arrow(draw, (1270, 660), (1480, 790), "email / push request")
    arrow(draw, (1480, 840), (480, 790), "customer notification")
    caption(draw, "Figure 8. System Architecture of the Mera's General Merchandise Store Management System")
    save(image, "figure-08-system-architecture.png")


def erd_diagram():
    image, draw = canvas("Entity-Relationship Diagram", "Core relational records of the Mera's Merchandise System")
    tables = [
        ((85, 220, 475, 510), "USERS\nPK id\nname\nemail\npassword\nrole\ncreated_at\nupdated_at", BLUE),
        ((805, 180, 1195, 515), "PRODUCTS\nPK id\nsku\nname\nunit\ncategory\nprice\nbulk_price\nbulk_min_qty\nquantity", GREEN),
        ((805, 720, 1195, 970), "SALES\nPK id\nFK user_id\ntotal\ncreated_at\nupdated_at", YELLOW),
        ((1390, 690, 1780, 980), "SALE_ITEMS\nPK id\nFK sale_id\nFK product_id\nqty\nprice\ncreated_at", PURPLE),
        ((80, 720, 470, 1050), "INQUIRIES\nPK id\nFK user_id\nFK responded_by\ncustomer_name\ncustomer_email\nsubject\nmessage\nstatus\nresponse\nfcm_token", RED),
        ((1390, 200, 1780, 470), "ACTIVITY_LOGS\nPK id\nFK user_id\nuser_name\nuser_role\naction\ndescription\nip_address\ncreated_at", "#F5F7FA"),
    ]
    for rectangle, value, colour in tables:
        box(draw, rectangle, value, colour, SMALL_FONT, 8)
    arrow(draw, (475, 380), (805, 845), "1 : many")
    arrow(draw, (1000, 515), (1585, 690), "1 : many")
    arrow(draw, (1195, 845), (1390, 845), "1 : many")
    arrow(draw, (475, 900), (805, 900), "customer")
    arrow(draw, (475, 310), (1390, 335), "1 : many")
    draw.text((WIDTH // 2, 1150), "PK = Primary Key     FK = Foreign Key     Relationship labels show parent-to-child cardinality", font=SMALL_FONT, fill="#4B5563", anchor="ma")
    caption(draw, "Figure 9. Entity-Relationship Diagram")
    save(image, "figure-09-entity-relationship-diagram.png")


def main():
    rad_lifecycle()
    gantt_chart()
    context_diagram()
    level_one_dfd()
    support_dfd()
    inventory_dfd()
    pos_dfd()
    system_architecture()
    erd_diagram()


if __name__ == "__main__":
    main()
