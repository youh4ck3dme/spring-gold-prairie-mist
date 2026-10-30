#!/usr/bin/env python3
"""
Build production-ready, 100% PclZip & ZipArchive compliant distribution zip
for Vorkei Statement Bridge WordPress plugin.
"""

import os
import sys
import zipfile

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

    file_count = 0
    with zipfile.ZipFile(OUT_ZIP, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9, strict_timestamps=False) as zf:
        for root, dirs, files in os.walk(SRC_DIR):
            dirs[:] = [d for d in dirs if d != 'tests' and not d.startswith('.')]
            for file in sorted(files):
                if file.startswith('.') or file.endswith('~') or file.endswith('.swp'):
                    continue
                abs_path = os.path.join(root, file)
                rel_path = os.path.relpath(abs_path, ROOT_DIR)

                zinfo = zipfile.ZipInfo(rel_path)
                zinfo.date_time = (2026, 10, 30, 12, 0, 0)
                zinfo.compress_type = zipfile.ZIP_DEFLATED
                zinfo.external_attr = 0o644 << 16

                with open(abs_path, 'rb') as f:
                    zf.writestr(zinfo, f.read())
                file_count += 1

    # Copy to public folder as well
    import shutil
    shutil.copyfile(OUT_ZIP, PUBLIC_ZIP)

    print(f"✓ Balíček úspešne vygenerovaný: {OUT_ZIP} a {PUBLIC_ZIP}")
    print(f"  Veľkosť: {os.path.getsize(OUT_ZIP):,} bajtov ({file_count} súborov)")

if __name__ == '__main__':
    build_zip()
