import { n as TSS_SERVER_FUNCTION, t as createServerFn } from "./ssr.mjs";
//#region node_modules/.nitro/vite/services/ssr/assets/unlock-gate-B84n9jYv.js
var createServerRpc = (serverFnMeta, splitImportFn) => {
	const url = "/_serverFn/" + serverFnMeta.id;
	return Object.assign(splitImportFn, {
		url,
		serverFnMeta,
		[TSS_SERVER_FUNCTION]: true
	});
};
var unlockStatement_createServerFn_handler = createServerRpc({
	id: "2b8e76eaec1f3f4278ec65fc4c18198b2bc26bbddf6a668700aacaca0ed8c0ea",
	name: "unlockStatement",
	filename: "src/lib/unlock-gate.ts"
}, (opts) => unlockStatement.__executeServer(opts));
var unlockStatement = createServerFn({ method: "POST" }).validator((data) => data).handler(unlockStatement_createServerFn_handler, async ({ data }) => {
	const { matchesUnlockPassword } = await import("./unlock-secret.server-CQCA9Vc-.mjs");
	return matchesUnlockPassword(data);
});
//#endregion
export { unlockStatement_createServerFn_handler };
