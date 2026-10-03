"""Shared registry for the MERAS flowchart definitions."""
CHARTS = []


def chart(slug, title, **kw):
    def deco(fn):
        CHARTS.append((slug, title, fn, kw))
        return fn
    return deco


def entry(c, code, view_text, view_row=1.7, circ="1", circ_col=-1):
    """Off-page entry connector, an on-page re-entry circle and the 'View ... page' box."""
    c.off("in", code, 0, 0)
    c.conn("c1", circ, circ_col, view_row - 0.85)
    c.proc("view", view_text, 0, view_row)
    c.e("in", "view")
    c.e("c1", "view", out="r", into="t")
