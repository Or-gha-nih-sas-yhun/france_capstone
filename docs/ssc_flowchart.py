"""
Flowchart renderer that reproduces the look of the SSC paper's system flowcharts
(draw.io, black outline on white):

    oval            Start / End
    parallelogram   input / URL
    rectangle       process / page          (heavy outline = completed action)
    diamond         decision
    cylinder        database table
    circle          on-page connector       (label = connector id)
    pentagon        off-page connector      (points down, label = target chart)

Nodes sit on a (col, row) grid (fractions allowed); edges are orthogonal and use
the four side ports 't', 'b', 'l', 'r'.  Pillow only.
"""
from pathlib import Path
from PIL import Image, ImageChops, ImageDraw, ImageFont

S = 3                      # drawing supersample
OUT_SCALE = 0.5            # final = S * OUT_SCALE = 1.5x of the base units
FONTS = Path("C:/Windows/Fonts")
INK = "#000000"
LW = 2
VEC = {"t": (0, -1), "b": (0, 1), "l": (-1, 0), "r": (1, 0)}


def _font(size):
    return ImageFont.truetype(str(FONTS / "arial.ttf"), int(size * S))


class Node:
    def __init__(self, nid, kind, lines, w, h, col, row):
        self.id, self.kind, self.lines, self.w, self.h = nid, kind, lines, w, h
        self.col, self.row = col, row
        self.cx = self.cy = 0.0

    def port(self, side):
        dx, dy = VEC[side]
        if self.kind == "off" and side == "b":
            return (self.cx, self.cy + self.h / 2)
        if self.kind == "io" and side in "lr":
            return (self.cx + dx * (self.w / 2 - 16), self.cy)
        return (self.cx + dx * self.w / 2, self.cy + dy * self.h / 2)


class Chart:
    def __init__(self, cw=300, rh=150, fs=21, wrap=190):
        self.cw, self.rh, self.fs, self.wrap = cw, rh, fs, wrap
        self.f = _font(fs)
        self.lh = fs * 1.22
        self.nodes, self.edges = {}, []

    # ---- text -----------------------------------------------------------
    def _wrap(self, text, maxw):
        out = []
        for para in text.split("\n"):
            cur = ""
            for word in para.split():
                trial = (cur + " " + word).strip()
                if not cur or self.f.getlength(trial) / S <= maxw:
                    cur = trial
                else:
                    out.append(cur)
                    cur = word
            out.append(cur)
        return out

    def _tw(self, lines):
        return max(self.f.getlength(l) / S for l in lines)

    # ---- nodes ----------------------------------------------------------
    def _add(self, nid, kind, lines, w, h, col, row):
        if nid in self.nodes:
            raise ValueError(f"duplicate node {nid}")
        n = Node(nid, kind, lines, w, h, col, row)
        self.nodes[nid] = n
        return n

    def term(self, nid, text, col, row):
        lines = [text]
        return self._add(nid, "term", lines, 150, 76, col, row)

    def proc(self, nid, text, col, row, heavy=False):
        lines = self._wrap(text, self.wrap)
        w = max(170, min(250, self._tw(lines) + 44))
        h = max(80, len(lines) * self.lh + 36)
        return self._add(nid, "heavy" if heavy else "proc", lines, w, h, col, row)

    def act(self, nid, text, col, row):
        return self.proc(nid, text, col, row, heavy=True)

    def io(self, nid, text, col, row):
        lines = self._wrap(text, self.wrap - 10)
        w = max(210, self._tw(lines) + 100)
        h = max(86, len(lines) * self.lh + 36)
        return self._add(nid, "io", lines, w, h, col, row)

    def dec(self, nid, text, col, row):
        lines = self._wrap(text, 150)
        tw, th = self._tw(lines), len(lines) * self.lh
        h = max(100, 2 * th + 44)
        w = max(190, (tw + 20) / max(0.35, 1 - (th + 14) / h))
        return self._add(nid, "dec", lines, w, h, col, row)

    def db(self, nid, text, col, row):
        lines = self._wrap(text, 190)
        w = max(200, self._tw(lines) + 40)
        h = max(112, len(lines) * self.lh + 62)
        return self._add(nid, "db", lines, w, h, col, row)

    def conn(self, nid, text, col, row):
        return self._add(nid, "conn", [text], 62, 62, col, row)

    def off(self, nid, text, col, row):
        return self._add(nid, "off", [text], 88, 92, col, row)

    # ---- edges ----------------------------------------------------------
    def e(self, a, b, label=None, out="b", into="t", via=None, at="start", mid=None):
        self.edges.append(dict(a=a, b=b, label=label, out=out, into=into, via=via, at=at, mid=mid))

    def _xy(self, col, row):
        return (self.x0 + col * self.cw, self.y0 + row * self.rh)

    def _layout(self):
        cols = [n.col for n in self.nodes.values()]
        rows = [n.row for n in self.nodes.values()]
        self.x0 = 40 - min(cols) * self.cw + max(n.w for n in self.nodes.values()) / 2
        self.y0 = 40 - min(rows) * self.rh + 50
        for n in self.nodes.values():
            n.cx, n.cy = self._xy(n.col, n.row)

    def _route(self, ed):
        A, B = self.nodes[ed["a"]], self.nodes[ed["b"]]
        s, t = A.port(ed["out"]), B.port(ed["into"])
        if ed["via"]:
            pts = [s] + [self._xy(*v) for v in ed["via"]] + [t]
        else:
            vo, vi = ed["out"] in "tb", ed["into"] in "tb"
            pts = [s]
            if vo and vi:
                if abs(s[0] - t[0]) > 0.5:
                    my = ed["mid"] if ed["mid"] is not None else (s[1] + t[1]) / 2
                    pts += [(s[0], my), (t[0], my)]
            elif not vo and not vi:
                if abs(s[1] - t[1]) > 0.5:
                    mx = ed["mid"] if ed["mid"] is not None else (s[0] + t[0]) / 2
                    pts += [(mx, s[1]), (mx, t[1])]
            elif vo and not vi:
                pts.append((s[0], t[1]))
            else:
                pts.append((t[0], s[1]))
            pts.append(t)
        clean = [pts[0]]
        for p in pts[1:]:
            if abs(p[0] - clean[-1][0]) > 0.5 or abs(p[1] - clean[-1][1]) > 0.5:
                clean.append(p)
        return clean

    # ---- drawing --------------------------------------------------------
    def _text(self, d, cx, cy, lines, dy=0):
        total = len(lines) * self.lh
        y = cy - total / 2 + dy
        for line in lines:
            d.text((cx * S, (y + self.lh / 2) * S), line, font=self.f, fill=INK, anchor="mm")
            y += self.lh

    def _draw_node(self, d, n):
        x1, y1 = (n.cx - n.w / 2) * S, (n.cy - n.h / 2) * S
        x2, y2 = (n.cx + n.w / 2) * S, (n.cy + n.h / 2) * S
        cx, cy = n.cx * S, n.cy * S
        lw = LW * S
        dy = 0
        if n.kind == "term":
            d.ellipse((x1, y1, x2, y2), fill="white", outline=INK, width=lw)
        elif n.kind == "conn":
            d.ellipse((x1, y1, x2, y2), fill="white", outline=INK, width=lw)
        elif n.kind in ("proc", "heavy"):
            d.rectangle((x1, y1, x2, y2), fill="white", outline=INK,
                        width=lw * (2 if n.kind == "heavy" else 1))
        elif n.kind == "io":
            k = 32 * S
            d.polygon([(x1 + k, y1), (x2, y1), (x2 - k, y2), (x1, y2)], fill="white")
            d.line([(x1 + k, y1), (x2, y1), (x2 - k, y2), (x1, y2), (x1 + k, y1)], fill=INK, width=lw,
                   joint="curve")
        elif n.kind == "dec":
            pts = [(cx, y1), (x2, cy), (cx, y2), (x1, cy)]
            d.polygon(pts, fill="white")
            d.line(pts + [pts[0]], fill=INK, width=lw, joint="curve")
        elif n.kind == "db":
            ry = 16 * S
            d.rectangle((x1, y1 + ry, x2, y2 - ry), fill="white")
            d.ellipse((x1, y2 - 2 * ry, x2, y2), fill="white", outline=INK, width=lw)
            d.rectangle((x1 + lw, y1 + ry, x2 - lw, y2 - ry), fill="white")
            d.line((x1, y1 + ry, x1, y2 - ry), fill=INK, width=lw)
            d.line((x2, y1 + ry, x2, y2 - ry), fill=INK, width=lw)
            d.ellipse((x1, y1, x2, y1 + 2 * ry), fill="white", outline=INK, width=lw)
            dy = 8
        elif n.kind == "off":
            tip = y2
            sh = y2 - 30 * S
            pts = [(x1, y1), (x2, y1), (x2, sh), (cx, tip), (x1, sh)]
            d.polygon(pts, fill="white")
            d.line(pts + [pts[0]], fill=INK, width=lw, joint="curve")
            dy = -8
        self._text(d, n.cx, n.cy, n.lines, dy)

    @staticmethod
    def _unit(p, q):
        dx, dy = q[0] - p[0], q[1] - p[1]
        m = max(abs(dx), abs(dy)) or 1
        return (dx / m, dy / m)

    def _arrow(self, d, tip, u):
        ln, hw = 16, 6.5
        bx, by = tip[0] - u[0] * ln, tip[1] - u[1] * ln
        px, py = -u[1], u[0]
        d.polygon([(tip[0] * S, tip[1] * S), ((bx + px * hw) * S, (by + py * hw) * S),
                   ((bx - px * hw) * S, (by - py * hw) * S)], fill=INK)

    def _draw_edge(self, d, ed, labels):
        pts = self._route(ed)
        for p, q in zip(pts, pts[1:]):
            d.line((p[0] * S, p[1] * S, q[0] * S, q[1] * S), fill=INK, width=LW * S)
        u = self._unit(pts[-2], pts[-1])
        end = pts[-1]
        # stop the line short of the arrow tip so the head stays crisp
        self._arrow(d, end, u)
        if ed["label"]:
            labels.append((pts, ed))

    def _draw_label(self, d, pts, ed):
        text = ed["label"]
        tw = self.f.getlength(text) / S
        th = self.lh
        if ed["at"] == "end":
            p, q = pts[-1], pts[-2]
            off = 52
        else:
            p, q = pts[0], pts[1]
            off = 30
        ux, uy = self._unit(p, q)
        seg = abs(q[0] - p[0]) + abs(q[1] - p[1])
        off = off + (tw / 2 if ux else th / 2)
        if seg < 2 * off + 30:
            off = (seg - 8) / 2 if ed["at"] == "end" else seg / 2
        cx, cy = p[0] + ux * off, p[1] + uy * off
        d.rectangle(((cx - tw / 2 - 3) * S, (cy - th / 2 + 1) * S, (cx + tw / 2 + 3) * S, (cy + th / 2 - 1) * S),
                    fill="white")
        d.text((cx * S, cy * S), text, font=self.f, fill=INK, anchor="mm")

    def render(self, path):
        self._layout()
        wmax = max(n.cx + n.w / 2 for n in self.nodes.values()) + 60
        hmax = max(n.cy + n.h / 2 for n in self.nodes.values()) + 60
        img = Image.new("RGB", (int(wmax * S), int(hmax * S)), "white")
        d = ImageDraw.Draw(img)
        labels = []
        for ed in self.edges:
            self._draw_edge(d, ed, labels)
        for n in self.nodes.values():
            self._draw_node(d, n)
        for pts, ed in labels:
            self._draw_label(d, pts, ed)
        bbox = ImageChops.difference(img, Image.new("RGB", img.size, "white")).getbbox()
        pad = 24 * S
        img = img.crop((max(0, bbox[0] - pad), max(0, bbox[1] - pad),
                        min(img.width, bbox[2] + pad), min(img.height, bbox[3] + pad)))
        img = img.resize((int(img.width * OUT_SCALE), int(img.height * OUT_SCALE)), Image.LANCZOS)
        Path(path).parent.mkdir(parents=True, exist_ok=True)
        img.save(path, optimize=True)
        return img.size
