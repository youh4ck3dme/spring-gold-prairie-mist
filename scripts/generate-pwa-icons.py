import os
from PIL import Image

SRC_IMG = "/home/vorkei/.gemini/antigravity/brain/e820881a-e798-42b8-9f7e-897e9aae22b7/vorkei_vypis_pwa_icon_clean_1793300749703.jpg"
PUBLIC_DIR = "/home/vorkei/Documents/pdf-t3-vorkei/public"

im = Image.open(SRC_IMG).convert("RGBA")
width, height = im.size
print(f"Source image loaded: {width}x{height}")

# Sizes to generate
SIZES = [
    ("pwa-512x512.png", 512, 512),
    ("pwa-192x192.png", 192, 192),
    ("apple-touch-icon.png", 180, 180),
    ("apple-touch-icon-180x180.png", 180, 180),
    ("apple-touch-icon-152x152.png", 152, 152),
    ("apple-touch-icon-120x120.png", 120, 120),
    ("favicon-32x32.png", 32, 32),
    ("favicon-16x16.png", 16, 16),
]

for name, w, h in SIZES:
    target_path = os.path.join(PUBLIC_DIR, name)
    resized = im.resize((w, h), Image.Resampling.LANCZOS)
    resized.save(target_path, "PNG", optimize=True)
    print(f"Generated: {name} ({w}x{h})")

# Maskable icon: In Android PWA maskable spec, 10% outer area can be cropped by system shape.
# Our source image already has ~20% dark margin around the central emblem, making it ideal as maskable!
# We also create explicit pwa-maskable-512x512.png and pwa-maskable-192x192.png
for name, w, h in [("pwa-maskable-512x512.png", 512, 512), ("pwa-maskable-192x192.png", 192, 192)]:
    target_path = os.path.join(PUBLIC_DIR, name)
    # Add a slight 5% padding with the background edge color to guarantee zero clip on any device
    bg_color = im.getpixel((10, 10))
    canvas = Image.new("RGBA", (w, h), bg_color)
    content_w = int(w * 0.90)
    content_h = int(h * 0.90)
    resized_content = im.resize((content_w, content_h), Image.Resampling.LANCZOS)
    offset_x = (w - content_w) // 2
    offset_y = (h - content_h) // 2
    canvas.paste(resized_content, (offset_x, offset_y))
    canvas.save(target_path, "PNG", optimize=True)
    print(f"Generated Maskable: {name} ({w}x{h})")

print("All PWA icons generated successfully!")
