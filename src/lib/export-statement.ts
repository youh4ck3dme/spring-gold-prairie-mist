import html2canvas from "html2canvas";
import { jsPDF } from "jspdf";
import JSZip from "jszip";

const PAGE: [number, number] = [595.28, 841.89];

function downloadBlob(blob: Blob, name: string) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = name;
  link.click();
  URL.revokeObjectURL(url);
}

async function paintSheet(sheet: HTMLElement): Promise<HTMLCanvasElement> {
  const host = document.createElement("div");
  host.style.cssText =
    "position:fixed;left:-2400px;top:0;width:595.28pt;height:841.89pt;overflow:hidden;pointer-events:none;";
  const clone = sheet.cloneNode(true) as HTMLElement;
  clone.style.transform = "none";
  clone.style.position = "relative";
  clone.style.left = "0";
  clone.style.top = "0";
  clone.style.boxShadow = "none";
  host.appendChild(clone);
  document.body.appendChild(host);
  try {
    return await html2canvas(clone, {
      scale: 3, // ~300 DPI tlačová ostrosť
      backgroundColor: "#ffffff",
      width: clone.offsetWidth,
      height: clone.offsetHeight,
      logging: false,
      useCORS: true,
    });
  } finally {
    host.remove();
  }
}

export type ExportOptions = boolean | { authorized?: boolean };

function isAuthorized(options?: ExportOptions): boolean {
  if (typeof options === "boolean") return options;
  return Boolean(options?.authorized);
}

async function buildPdf(
  sheets: HTMLElement[],
  stem?: string,
  options?: ExportOptions,
): Promise<Blob> {
  const authorized = isAuthorized(options);
  const hasWatermark = sheets.some((sheet) => sheet.querySelector(".watermark") !== null);
  if (hasWatermark && !authorized) {
    throw new Error("Dokument je zamknutý. Export bez autorizácie nie je povolený.");
  }
  const pdf = new jsPDF({ unit: "pt", format: PAGE, compress: true });

  pdf.setProperties({
    title: stem || "Výpis z účtu",
    subject: "Výpis z bankového účtu",
    author: "Tatra banka, a.s. / vorkei",
    creator: "pdf-t3.vorkei",
  });

  for (let index = 0; index < sheets.length; index += 1) {
    const canvas = await paintSheet(sheets[index]);
    const image = canvas.toDataURL("image/jpeg", 0.95);
    if (index > 0) pdf.addPage(PAGE);
    pdf.addImage(image, "JPEG", 0, 0, PAGE[0], PAGE[1]);
  }
  return pdf.output("blob");
}

export async function downloadStatementPdf(
  sheets: HTMLElement[],
  stem: string,
  options?: ExportOptions,
) {
  downloadBlob(await buildPdf(sheets, stem, options), `${stem}.pdf`);
}

export async function downloadStatementZip(
  sheets: HTMLElement[],
  stem: string,
  options?: ExportOptions,
) {
  const zip = new JSZip();
  zip.file(`${stem}.pdf`, await buildPdf(sheets, stem, options));
  downloadBlob(await zip.generateAsync({ type: "blob" }), `${stem}.zip`);
}
