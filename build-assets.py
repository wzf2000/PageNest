from pathlib import Path
import hashlib

assets = Path(__file__).resolve().parent / "assets"
mapping = {}
for key, ext, name in [
    ("css", "css", "layout.css"),
    ("js", "js", "navigation.js"),
    ("reading", "js", "reading.js"),
    ("login", "css", "login.css"),
]:
    content = (assets / name).read_bytes()
    target = Path(name).stem + "-" + hashlib.sha256(content).hexdigest()[:12] + "." + ext
    (assets / target).write_bytes(content)
    mapping[key] = target
(assets / "manifest.php").write_text(
    "<?php\nreturn [\n"
    + "".join("    " + repr(k) + " => " + repr(v) + ",\n" for k, v in mapping.items())
    + "];\n"
)
