"""
Tiny orthogonal flowchart renderer (Pillow only) used for the Chapter III figures.

Nodes are placed on a (column, row) grid, rows are sized to their tallest node,
and edges are routed with right-angle segments between side ports
('t'op, 'b'ottom, 'l'eft, 'r'ight).  The look follows Figure 18 of the thesis:
green terminators, blue process boxes, yellow decisions.
"""
from pathlib import Path
from PIL import Image, ImageChops, ImageDraw, ImageFont

SS = 2  # supersampling factor
FONTS = Path("C:/Windows/Fonts")

STYLE = {  # fill, outline
    "term": ("#E2EFDA", "#548235"),
    "proc": ("#EAF0FA", "#2F5597"),
    "dec": ("#FFF2CC", "#BF9000"),
    "store": ("#EDE7F6", "#6A4C93"),
    "ref": ("#FCE4D6", "#C55A11"),
}
INK = "#111111"
LINE = "#404040"
VEC = {"t": (0, -1), "b": (0, 1), "l": (-1, 0), "r": (1, 0)}


def load_font(size, bold=False):
    name = "arialbd.ttf" if bold else "arial.ttf"
    return ImageFont.truetype(str(FONTS / name), int(size * SS))


class Node:
    def __init__(self, nid, kind, lines, w, h, title=None):
        self.id, self.kind, self.lines, self.w, self.h = nid, kind, lines, w, h
        self.title = title or []
        self.col = self.row = 0
        self.cx = self.cy = 0.0

    def port(self, side):
        dx, dy = VEC[side]
        return (self.cx + dx * self.w / 2, self.cy + dy * self.h / 2)


class Chart:
    def __init__(self, cols=3, width=1280, fs=23, center_w=400, side_w=300, row_gap=58):
        self.cols, self.width, self.fs = cols, width, fs
        self.center_w, self.side_w, self.row_gap = center_w, side_w, row_gap
        self.f = load_font(fs)
        self.fb = load_font(fs, True)
        self.lh = fs * 1.24
        self.nodes, self.edges = {}, []
        self.extra_gap = {}
        self.lane_l, self.lane_r = 26, width - 26

    # -- text helpers -------------------------------------------------------
    def _wrap(self, text, maxw):
        out = []
        for para in text.split("\n"):
            cur = ""
            for word in para.split():
                trial = (cur + " " + word).strip()
                if not cur or self.f.getlength(trial) / SS <= maxw:
                    cur = trial
                else:
                    out.append(cur)
                    cur = word
            out.append(cur)
        return out

    def _tw(self, lines):
        return max(self.f.getlength(l) / SS for l in lines)

    def _wrap_bold(self, text, maxw):
        out, cur = [], ""
        for word in text.split():
            trial = (cur + " " + word).strip()
            if not cur or self.fb.getlength(trial) / SS <= maxw:
                cur = trial
            else:
                out.append(cur)
                cur = word
        out.append(cur)
        return out

    def _wrap_paras(self, text, maxw):
        lines = []
        for part in text.split("\n"):
            lines.extend(self._wrap(part, maxw))
        return lines

    # -- nodes ----------------------------------------------------------------
    def n(self, nid, kind, text, col, row, w=None, min_h=0, title=None):
        """Add a node. `row` may be fractional (placed between two rows), `min_h` sets a minimum
        height, and `title` adds a bold heading above the text of a box or data store."""
        centre = (self.cols - 1) / 2
        tl = []
        if kind == "text":                      # plain label without a shape
            w = w or self.center_w
            lines = self._wrap_paras(text, w)
            h = len(lines) * self.lh + 6
        elif kind == "dec":
            w = w or 360
            lines = self._wrap(text, w * 0.5)
            tw, th = self._tw(lines), len(lines) * self.lh
            h = max(112, (th + 16) / max(0.3, 1 - (tw + 16) / w))
        elif kind == "store":
            w = w or 210
            lines = self._wrap_paras(text, w - 24)
            tl = self._wrap_bold(title, w - 24) if title else []
            h = (len(lines) + len(tl)) * self.lh + 54
        elif kind == "term":
            w = w or (self.center_w if col == centre else self.side_w)
            lines = self._wrap(text, w - 70)
            w = min(w, max(170, self._tw(lines) + 70))
            h = len(lines) * self.lh + 26
        else:
            w = w or (self.center_w if col == centre else self.side_w)
            lines = self._wrap_paras(text, w - 30)
            tl = self._wrap_bold(title, w - 30) if title else []
            h = (len(lines) + len(tl)) * self.lh + 30 + (6 if tl else 0)
        node = Node(nid, kind, lines, w, max(h, min_h), tl)
        node.col, node.row = col, row
        self.nodes[nid] = node
        return node

    def gap(self, row, extra):
        """Extra vertical space after a row (for long labels)."""
        self.extra_gap[row] = extra

    def _layout(self):
        whole = [n for n in self.nodes.values() if float(n.row).is_integer()]
        rows = sorted({int(n.row) for n in whole})
        y, centres = 34, {}
        for r in range(min(rows), max(rows) + 1):
            hs = [n.h for n in whole if int(n.row) == r]
            hh = max(hs) if hs else 0
            centres[r] = y + hh / 2
            y += hh + (self.row_gap if hs else 0) + self.extra_gap.get(r, 0)
        for n in self.nodes.values():
            n.cx = self.width * (2 * n.col + 1) / (2 * self.cols)
            lo = int(n.row // 1)
            hi = lo if float(n.row).is_integer() else lo + 1
            frac = n.row - lo
            n.cy = centres[lo] + (centres[hi] - centres[lo]) * frac

    # -- edges ----------------------------------------------------------------
    def e(self, a, b, label=None, out="b", into="t", lane=None, mid=None, dash=False,
          arrows="end", at="start", via=None):
        self.edges.append(dict(a=a, b=b, label=label, out=out, into=into, lane=lane, mid=mid,
                               dash=dash, arrows=arrows, at=at, via=via))

    def link(self, a, b, out="l", into="r"):
        """Dashed read/write link to a data store."""
        self.e(a, b, out=out, into=into, dash=True, arrows="both")

    def _route(self, ed):
        A, B = self.nodes[ed["a"]], self.nodes[ed["b"]]
        S, E = A.port(ed["out"]), B.port(ed["into"])
        if ed["via"]:
            pts = [S] + list(ed["via"]) + [E]
        elif ed["lane"]:
            lx = self.lane_l if ed["lane"] == "L" else self.lane_r
            pts = [S, (lx, S[1]), (lx, E[1]), E]
        else:
            vo, vi = ed["out"] in "tb", ed["into"] in "tb"
            pts = [S]
            if vo and vi:
                if abs(S[0] - E[0]) > 0.5:
                    my = ed["mid"] if ed["mid"] is not None else (S[1] + E[1]) / 2
                    pts += [(S[0], my), (E[0], my)]
            elif not vo and not vi:
                if abs(S[1] - E[1]) > 0.5:
                    mx = ed["mid"] if ed["mid"] is not None else (S[0] + E[0]) / 2
                    pts += [(mx, S[1]), (mx, E[1])]
            elif vo and not vi:
                pts += [(S[0], E[1])]
            else:
                pts += [(E[0], S[1])]
            pts.append(E)
        clean = [pts[0]]
        for p in pts[1:]:
            if abs(p[0] - clean[-1][0]) > 0.5 or abs(p[1] - clean[-1][1]) > 0.5:
                clean.append(p)
        return clean

    # -- drawing ----------------------------------------------------------------
    def _text(self, d, cx, cy, lines, bold=False, title=()):
        rows = [(l, True) for l in title] + [(l, bold) for l in lines]
        total = len(rows) * self.lh + (6 if title else 0)
        y = cy - total / 2
        for i, (line, is_bold) in enumerate(rows):
            d.text((cx * SS, (y + self.lh / 2) * SS), line, font=self.fb if is_bold else self.f,
                   fill=INK, anchor="mm")
            y += self.lh + (6 if title and i == len(title) - 1 else 0)

    def _draw_node(self, d, n):
        if n.kind == "text":
            self._text(d, n.cx, n.cy, n.lines, bold=True)
            return
        fill, outline = STYLE[n.kind]
        x1, y1 = (n.cx - n.w / 2) * SS, (n.cy - n.h / 2) * SS
        x2, y2 = (n.cx + n.w / 2) * SS, (n.cy + n.h / 2) * SS
        lw = 3 * SS // 2 + 1
        if n.kind == "term":
            d.rounded_rectangle((x1, y1, x2, y2), radius=(y2 - y1) / 2, fill=fill, outline=outline, width=lw)
        elif n.kind == "dec":
            d.polygon([(n.cx * SS, y1), (x2, n.cy * SS), (n.cx * SS, y2), (x1, n.cy * SS)],
                      fill=fill, outline=outline, width=lw)
        elif n.kind == "store":
            ry = 11 * SS
            d.rectangle((x1, y1 + ry, x2, y2 - ry), fill=fill)
            d.ellipse((x1, y2 - 2 * ry, x2, y2), fill=fill, outline=outline, width=lw)
            d.rectangle((x1 + lw, y1 + ry, x2 - lw, y2 - ry), fill=fill)
            d.line((x1, y1 + ry, x1, y2 - ry), fill=outline, width=lw)
            d.line((x2, y1 + ry, x2, y2 - ry), fill=outline, width=lw)
            d.ellipse((x1, y1, x2, y1 + 2 * ry), fill=fill, outline=outline, width=lw)
        else:
            d.rounded_rectangle((x1, y1, x2, y2), radius=6 * SS, fill=fill, outline=outline, width=lw)
        self._text(d, n.cx, n.cy + (4 if n.kind == "store" else 0), n.lines,
                   bold=n.kind in ("term",) or (n.kind == "store" and not n.title), title=n.title)

    def _arrowhead(self, d, tip, direction):
        dx, dy = direction
        ln, hw = 15, 7.5
        bx, by = tip[0] - dx * ln, tip[1] - dy * ln
        px, py = -dy, dx
        d.polygon([(tip[0] * SS, tip[1] * SS), ((bx + px * hw) * SS, (by + py * hw) * SS),
                   ((bx - px * hw) * SS, (by - py * hw) * SS)], fill=LINE)

    @staticmethod
    def _unit(p, q):
        dx, dy = q[0] - p[0], q[1] - p[1]
        n = max(abs(dx), abs(dy)) or 1
        return (dx / n, dy / n)

    def _dashed(self, d, p, q):
        dx, dy = q[0] - p[0], q[1] - p[1]
        length = (dx * dx + dy * dy) ** 0.5
        if not length:
            return
        ux, uy = dx / length, dy / length
        pos = 0.0
        while pos < length:
            end = min(pos + 9, length)
            d.line((((p[0] + ux * pos) * SS), (p[1] + uy * pos) * SS, (p[0] + ux * end) * SS,
                    (p[1] + uy * end) * SS), fill=LINE, width=2 * SS)
            pos += 15

    def _draw_edge(self, d, ed, labels):
        pts = self._route(ed)
        for p, q in zip(pts, pts[1:]):
            if ed["dash"]:
                self._dashed(d, p, q)
            else:
                d.line((p[0] * SS, p[1] * SS, q[0] * SS, q[1] * SS), fill=LINE, width=2 * SS + 1)
        if ed["arrows"] in ("end", "both"):
            self._arrowhead(d, pts[-1], self._unit(pts[-2], pts[-1]))
        if ed["arrows"] == "both":
            self._arrowhead(d, pts[0], self._unit(pts[1], pts[0]))
        if ed["label"]:
            labels.append((pts, ed))

    def _draw_label(self, d, pts, ed):
        lines = ed["label"].split("\n")
        tw = max(self.f.getlength(l) / SS for l in lines)
        th = len(lines) * self.lh
        if ed["at"] == "end":
            p, q = pts[-1], pts[-2]
        else:
            p, q = pts[0], pts[1]
        ux, uy = self._unit(p, q)           # direction pointing away from the anchor
        if abs(ux) > 0:                     # horizontal segment: label above the line
            cx = p[0] + ux * (14 + tw / 2)
            cy = p[1] - th / 2 - 9
        else:                               # vertical segment: label beside the line
            cx = p[0] + 12 + tw / 2
            cy = p[1] + uy * (14 + th / 2)
        pad = 3
        d.rectangle(((cx - tw / 2 - pad) * SS, (cy - th / 2 - pad) * SS,
                     (cx + tw / 2 + pad) * SS, (cy + th / 2 + pad) * SS), fill="#FFFFFF")
        self._text(d, cx, cy, lines)

    def render(self, path):
        self._layout()
        bottom = max(n.cy + n.h / 2 for n in self.nodes.values()) + 40
        img = Image.new("RGB", (int(self.width * SS), int(bottom * SS)), "#FFFFFF")
        d = ImageDraw.Draw(img)
        labels = []
        for ed in self.edges:
            self._draw_edge(d, ed, labels)
        for n in self.nodes.values():
            self._draw_node(d, n)
        for pts, ed in labels:
            self._draw_label(d, pts, ed)
        bbox = ImageChops.difference(img, Image.new("RGB", img.size, "#FFFFFF")).getbbox()
        pad = 16 * SS
        box = (max(0, bbox[0] - pad), max(0, bbox[1] - pad),
               min(img.width, bbox[2] + pad), min(img.height, bbox[3] + pad))
        img = img.crop(box)
        Path(path).parent.mkdir(parents=True, exist_ok=True)
        img.save(path, optimize=True)
        return img.size
