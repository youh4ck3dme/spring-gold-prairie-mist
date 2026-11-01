#!/usr/bin/env python3
"""
Build production-ready, 100% PclZip & ZipArchive compliant distribution zip
for Vorkei Statement Bridge WordPress plugin.

Uses standard Unix `zip -r -9 -X` to strip extended filesystem attributes (xattrs,
extended timestamps, UID/GID tags) that break legacy PclZip central directory parsing
in WordPress Core (class-pclzip.php on PHP 8.x).
"""

import os
import sys
import shutil
import subprocess

ROOT_DIR = os.path.dirname(os.path.abspath(__file__))
SRC_DIR = os.path.join(ROOT_DIR, 'vorkei-statement-bridge')
OUT_ZIP = os.path.join(ROOT_DIR, 'vorkei-statement-bridge.zip')
PUBLIC_ZIP = os.path.join(ROOT_DIR, 'public', 'vorkei-statement-bridge.zip')

def build_zip():
    if not os.path.isdir(SRC_DIR):
        print(f"Error: {SRC_DIR} does not exist", file=sys.stderr)
        sys.exit(1)

    for target in [OUT_ZIP, PUBLIC_ZIP]:
        if os.path.exists(target):
            os.remove(target)

    # Use zip -r -9 -X for 100% PclZip compatibility
    zip_cmd = [
        "zip",
        "-r",
        "-9",
        "-X",
        OUT_ZIP,
        "vorkei-statement-bridge",
        "-x",
        "vorkei-statement-bridge/tests/*",
        "vorkei-statement-bridge/.*",
        "vorkei-statement-bridge/**/.*",
        "vorkei-statement-bridge/*~",
        "vorkei-statement-bridge/*.swp"
    ]

    result = subprocess.run(zip_cmd, cwd=ROOT_DIR, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"Error executing zip: {result.stderr}", file=sys.stderr)
        sys.exit(result.returncode)

    # Ensure public directory exists and copy release zip
    os.makedirs(os.path.dirname(PUBLIC_ZIP), exist_ok=True)
    shutil.copyfile(OUT_ZIP, PUBLIC_ZIP)

    # Verify zip size
    size_bytes = os.path.getsize(OUT_ZIP)
    print(f"✓ Balíček úspešne vygenerovaný cez zip -r -9 -X:")
    print(f"  Root:   {OUT_ZIP}")
    print(f"  Public: {PUBLIC_ZIP}")
    print(f"  Veľkosť: {size_bytes:,} bajtov")

if __name__ == '__main__':
    build_zip()
