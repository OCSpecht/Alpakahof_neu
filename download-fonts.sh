#!/bin/bash
# Rancho Cascada – Google Fonts lokal herunterladen
# Einmalig ausführen: bash download-fonts.sh
# Voraussetzung: Python 3 und curl (auf Mac standardmäßig vorhanden)

set -e
DIR="$(cd "$(dirname "$0")" && pwd)"
FONTS_DIR="$DIR/fonts"
mkdir -p "$FONTS_DIR"

UA="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36"

echo "Lade Playfair Display & Lato von Google Fonts..."

CSS=$(curl -sL -H "User-Agent: $UA" \
  "https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap")

if [ -z "$CSS" ]; then
  echo "Fehler: CSS konnte nicht geladen werden. Bitte Internetverbindung prüfen."
  exit 1
fi

python3 - "$FONTS_DIR" "$CSS" << 'PYEOF'
import sys, re, subprocess, os

fonts_dir = sys.argv[1]
css = sys.argv[2]

UA = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36"

# Map: (family_short, weight) -> local filename
name_map = {
    ("playfair-display", "400"): "playfair-display-400.woff2",
    ("playfair-display", "700"): "playfair-display-700.woff2",
    ("lato",             "300"): "lato-300.woff2",
    ("lato",             "400"): "lato-400.woff2",
    ("lato",             "700"): "lato-700.woff2",
}

# Parse @font-face blocks
blocks = re.findall(r'@font-face\s*\{([^}]+)\}', css)
downloaded = {}

for block in blocks:
    # Only keep latin subset
    if '/* latin */' not in block and '/* latin-ext */' in block:
        continue
    family_m = re.search(r"font-family:\s*'([^']+)'", block)
    weight_m = re.search(r'font-weight:\s*(\d+)', block)
    url_m    = re.search(r'url\((https://fonts\.gstatic\.com/[^ )]+\.woff2)\)', block)
    if not (family_m and weight_m and url_m):
        continue

    fam_key = family_m.group(1).lower().replace(" ", "-")
    weight  = weight_m.group(1)
    url     = url_m.group(1)
    key     = (fam_key, weight)

    if key in downloaded:
        continue  # skip duplicates (latin-ext etc.)
    if key not in name_map:
        continue

    local_name = name_map[key]
    out_path = os.path.join(fonts_dir, local_name)
    print(f"  Lade {local_name}...")
    subprocess.run(
        ["curl", "-sL", "-H", f"User-Agent: {UA}", url, "-o", out_path],
        check=True
    )
    downloaded[key] = local_name

# Write fonts.css
css_out = "/* Google Fonts – lokal gehostet */\n\n"
entries = [
    ("Playfair Display", "normal", "400", "playfair-display-400.woff2"),
    ("Playfair Display", "normal", "700", "playfair-display-700.woff2"),
    ("Lato",             "normal", "300", "lato-300.woff2"),
    ("Lato",             "normal", "400", "lato-400.woff2"),
    ("Lato",             "normal", "700", "lato-700.woff2"),
]
for family, style, weight, fname in entries:
    css_out += f"""@font-face {{
  font-family: '{family}';
  font-style: {style};
  font-weight: {weight};
  font-display: swap;
  src: url('{fname}') format('woff2');
}}\n\n"""

with open(os.path.join(fonts_dir, "fonts.css"), "w") as f:
    f.write(css_out)

print(f"\n✓ {len(downloaded)} Fontdateien + fonts.css gespeichert in: {fonts_dir}")
PYEOF
