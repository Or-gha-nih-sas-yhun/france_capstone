"""
MERAS system flowcharts drawn in the style of the SSC paper's "System Flowchart" section.
Every chart is traced from the Laravel routes/controllers (routes/web.php, app/Http/Controllers).

Run:  python docs/generate_meras_flowcharts.py
Output: docs/chapter-3-diagrams/flowcharts-ssc/fc-NN-*.png
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from ssc_flowchart import Chart

from flowchart_registry import CHARTS, chart

OUT = Path(__file__).parent / "chapter-3-diagrams" / "flowcharts-ssc"


# --------------------------------------------------------------------------- 1
@chart("01-url-home-page", "Flowchart 1. Start, URL and Home Page", cw=250, rh=150)
def main_chart(c):
    c.term("start", "Start", 0, 0)
    c.conn("A", "A", -1, 1)
    c.io("url", "URL", 0, 1.6)
    c.dec("d_admin", "If URL == Admin Pages", 1.15, 1.6)
    c.dec("d_cust", "If URL == Customer Pages", 2.35, 1.6)
    c.dec("d_mob", "If URL == Mobile App", 3.55, 1.6)
    c.conn("A2", "A", 4.65, 1.6)
    c.off("AA", "AA", 1.15, 3.0)
    c.off("AB", "AB", 2.35, 3.0)
    c.off("AC", "AC", 3.55, 3.0)
    c.conn("B", "B", -1, 3.0)
    c.proc("home", "Home Page", 0, 3.6)

    c.e("start", "url")
    c.e("A", "url", out="r", into="t")
    c.e("url", "d_admin", out="r", into="l")
    c.e("d_admin", "d_cust", "No", out="r", into="l")
    c.e("d_cust", "d_mob", "No", out="r", into="l")
    c.e("d_mob", "A2", "No", out="r", into="l")
    c.e("d_admin", "AA", "Yes")
    c.e("d_cust", "AB", "Yes")
    c.e("d_mob", "AC", "Yes")
    c.e("url", "home", via=[(0, 2.6)])
    c.e("B", "home", out="r", into="t")

    rows = [  # decision, label, yes-target
        ("d_home", "Home", "p_home", "Display Home Section"),
        ("d_prod", "Products", "p_prod", "Display Products Section"),
        ("d_inq", "Send Inquiry", "p_inq", "Display Inquiry Form"),
        ("d_chat", "Chat Support", "p_chat", "Open Chatbot Widget"),
    ]
    r = 5.0
    prev = "home"
    for did, text, pid, ptext in rows:
        c.dec(did, text, 0, r)
        c.e(prev, did, "No" if prev != "home" else None)
        c.proc(pid, ptext, 1, r)
        c.e(did, pid, "Yes", out="r", into="l")
        prev = did
        r += 1.45
    c.conn("B1", "B", 2, 5.0)
    c.e("p_home", "B1", out="r", into="l")
    c.off("AH", "AH", 2, 6.45)
    c.off("AI", "AI", 2, 7.9)
    c.off("AJ", "AJ", 2, 9.35)
    c.e("p_prod", "AH", out="r", into="l")
    c.e("p_inq", "AI", out="r", into="l")
    c.e("p_chat", "AJ", out="r", into="l")

    c.dec("d_login", "Login", 0, r)
    c.off("AE", "AE", 1, r)
    c.e(prev, "d_login", "No")
    c.e("d_login", "AE", "Yes", out="r", into="l")
    r += 1.45
    c.dec("d_reg", "Register / Create Account", 0, r)
    c.off("AF", "AF", 1, r)
    c.e("d_login", "d_reg", "No")
    c.e("d_reg", "AF", "Yes", out="r", into="l")
    r += 1.6
    c.conn("D", "D", -1, r)
    c.term("end", "End", 0, r)
    c.e("d_reg", "end", "No")
    c.e("D", "end", out="r", into="l")


import meras_charts_auth      # noqa: E402,F401  (charts 2-12)
import meras_charts_admin     # noqa: E402,F401  (charts 13-22)


def build(only=None):
    OUT.mkdir(parents=True, exist_ok=True)
    made = []
    for slug, title, fn, kw in CHARTS:
        if only and not any(slug.startswith(o) for o in only):
            continue
        kw = dict(kw)
        if not slug.startswith("01"):
            kw["cw"] = max(kw.get("cw", 300), 335)
        c = Chart(**kw)
        fn(c)
        path = OUT / f"fc-{slug}.png"
        size = c.render(path)
        made.append((slug, title, path, size))
        print(f"{path.name}  {size[0]}x{size[1]}")
    return made


if __name__ == "__main__":
    build(sys.argv[1:] or None)
