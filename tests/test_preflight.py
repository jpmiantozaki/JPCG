import json
from pathlib import Path
import re
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
def read(path):
    return (ROOT / path).read_text(encoding="utf-8")

class Preflight(unittest.TestCase):
    def test_active_deployment_root(self):
        self.assertIn("COPY public/ /var/www/html/", read("Dockerfile"))
    def test_pages_allowlist_matches_routes(self):
        routes = json.loads(read("cloudflare-pages/public/_routes.json"))["include"]
        block = read("cloudflare-pages/functions/[[path]].js").split("const ALLOWED_PATHS = new Set([",1)[1].split("]);",1)[0]
        paths = re.findall(r'"(/[^"\\n]+)"', block)
        self.assertEqual(set(routes), set(paths))
        self.assertEqual(len(routes), len(set(routes)))
    def test_membership_routes_dont_forward_to_original(self):
        for p in ("public/authfirst.php", "public/verify.php", "public/redeemvip.php"):
            self.assertNotIn("_cgm_session_bridge.php", read(p))
    def test_bridge_is_explicitly_documented(self):
        self.assertIn("CGM_ORIGINAL_ORIGIN", read("public/_cgm_session_bridge.php"))
        self.assertIn("not BASE/MOD independent", read("README.md"))
    def test_admin_key_uses_header_only(self):
        body = read("public/_common.php").split("function admin_ok(): bool {",1)[1].split("}\n",1)[0]
        self.assertIn("HTTP_X_ADMIN_KEY", body)
        self.assertNotIn("value('admin_key')", body)
        diag = read("public/admin_diag.php")
        self.assertNotIn("env_length", diag)
        self.assertNotIn("header_length", diag)
        result = subprocess.run(["php","public/admin_diag.php"], cwd=ROOT, text=True, capture_output=True, check=True)
        self.assertEqual(json.loads(result.stdout)["error"], "unauthorized")
    def test_index_is_informational(self):
        result = subprocess.run(["php","public/index.php"], cwd=ROOT, text=True, capture_output=True, check=True)
        data = json.loads(result.stdout)
        self.assertEqual(data["scope"], "INFORMATION_ONLY")
        self.assertEqual(data["game_session_provider"], "NOT_VERIFIED")

if __name__ == "__main__":
    unittest.main()
