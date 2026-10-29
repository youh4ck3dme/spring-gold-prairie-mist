import { existsSync } from "node:fs";
import { registerHooks } from "node:module";
import path from "node:path";
import { pathToFileURL } from "node:url";

const root = path.resolve(import.meta.dirname, "..", "..");

registerHooks({
  resolve(specifier, context, nextResolve) {
    if (!specifier.startsWith("@/")) return nextResolve(specifier, context);
    const mapped = path.join(root, "src", specifier.slice(2));
    const file = existsSync(`${mapped}.ts`)
      ? `${mapped}.ts`
      : existsSync(path.join(mapped, "index.ts"))
        ? path.join(mapped, "index.ts")
        : mapped;
    return nextResolve(pathToFileURL(file).href, context);
  },
});
