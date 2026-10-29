import { createServerFn } from "@tanstack/react-start";

export const unlockStatement = createServerFn({ method: "POST" })
  .validator((data: string) => data)
  .handler(async ({ data }) => {
    const { matchesUnlockPassword } = await import("./unlock-secret.server");
    return matchesUnlockPassword(data);
  });
