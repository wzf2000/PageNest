"""Portable MO catalogs retain native language plural rules, independent of Chinese."""

import gettext
import importlib.util
import io
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location(
    "theme_i18n", Path(__file__).resolve().parent.parent / "tools/i18n.py"
)
i18n = importlib.util.module_from_spec(spec)
spec.loader.exec_module(i18n)


class CatalogPlurals(unittest.TestCase):
    def entries(self, formula, forms):
        return {
            "": {"msgstr": i18n.header("ar", formula)},
            "%s item": {
                "msgid": "%s item",
                "msgid_plural": "%s items",
                **{f"msgstr[{n}]": text for n, text in enumerate(forms)},
            },
        }

    def test_two_forms_round_trip(self):
        entries = self.entries("nplurals=2; plural=(n != 1);", ["%s singular", "%s plural"])
        data = i18n.validate([{"id": "%s item", "plural": "%s items"}], entries)
        reader = gettext.GNUTranslations(io.BytesIO(data))
        self.assertEqual(reader.ngettext("%s item", "%s items", 1), "%s singular")
        self.assertEqual(reader.ngettext("%s item", "%s items", 0), "%s plural")

    def test_six_forms_round_trip(self):
        forms = ["%s zero", "%s one", "%s two", "%s few", "%s many", "%s other"]
        entries = self.entries(
            "nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5);",
            forms,
        )
        reader = gettext.GNUTranslations(
            io.BytesIO(i18n.validate([{"id": "%s item", "plural": "%s items"}], entries))
        )
        for number, index in [(0, 0), (1, 1), (2, 2), (3, 3), (11, 4), (100, 5)]:
            self.assertEqual(reader.ngettext("%s item", "%s items", number), forms[index])

    def test_missing_form_and_placeholder_rejected(self):
        entries = self.entries("nplurals=2; plural=(n != 1);", ["%s one"])
        with self.assertRaises(AssertionError):
            i18n.validate([{"id": "%s item", "plural": "%s items"}], entries)
        entries["%s item"]["msgstr[1]"] = "missing placeholder"
        with self.assertRaises(AssertionError):
            i18n.validate([{"id": "%s item", "plural": "%s items"}], entries)
