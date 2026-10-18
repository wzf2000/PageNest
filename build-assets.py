from pathlib import Path
import hashlib

assets = Path(__file__).resolve().parent / "assets"
mapping = {}
for key, ext, name in [
    ("css", "css", "layout.css"),
    ("editorial", "css", "editorial.css"),
    ("admin_design", "css", "admin-design.css"),
    ("customizer_design", "css", "customizer-design.css"),
    ("customizer_design_js", "js", "customizer-design.js"),
    ("appearance", "css", "appearance.css"),
    ("browsing", "css", "browsing.css"),
    ("presentation_css", "css", "presentation.css"),
    ("presentation_js", "js", "presentation.js"),
    ("reader_preferences_css", "css", "reader-preferences.css"),
    ("reader_preferences_js", "js", "reader-preferences.js"),
    ("discovery_css", "css", "discovery.css"),
    ("discovery_js", "js", "discovery.js"),
    ("js", "js", "navigation.js"),
    ("reading", "js", "reading.js"),
    ("login", "css", "login.css"),
    ("content", "css", "content.css"),
    ("content_js", "js", "content.js"),
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
