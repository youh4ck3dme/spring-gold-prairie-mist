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
  clone.querySelectorAll(".watermark").forEach((node) => node.remove());
  clone.style.transform = "none";
  clone.style.position = "relative";
  clone.style.left = "0";
  clone.style.top = "0";
  clone.style.boxShadow = "none";
  host.appendChild(clone);
  document.body.appendChild(host);
  try {
    return await html2canvas(clone, {
      scale: 2,
      backgroundColor: "#ffffff",
      width: clone.offsetWidth,
      height: clone.offsetHeight,
      logging: false,
    });
  } finally {
    host.remove();
  }
}

async function buildPdf(sheets: HTMLElement[]): Promise<Blob> {
  const pdf = new jsPDF({ unit: "pt", format: PAGE, compress: true });
  for (let index = 0; index < sheets.length; index += 1) {
    const canvas = await paintSheet(sheets[index]);
    const image = canvas.toDataURL("image/jpeg", 0.92);
    if (index > 0) pdf.addPage(PAGE);
    pdf.addImage(image, "JPEG", 0, 0, PAGE[0], PAGE[1]);
  }
  return pdf.output("blob");
}

export async function downloadStatementPdf(sheets: HTMLElement[], stem: string) {
  downloadBlob(await buildPdf(sheets), `${stem}.pdf`);
}

export async function downloadStatementZip(sheets: HTMLElement[], stem: string) {
  const zip = new JSZip();
  zip.file(`${stem}.pdf`, await buildPdf(sheets));
  downloadBlob(await zip.generateAsync({ type: "blob" }), `${stem}.zip`);
}
