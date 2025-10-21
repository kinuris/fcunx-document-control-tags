import { createAppConfig } from "@nextcloud/vite-config";
import { join, resolve } from "path";

const isProduction = process.env.NODE_ENV === "production";

export default createAppConfig(
  {
    tagCounterWidget: resolve(join("src", "tagCounterWidget.js")),
  },
  {
    config: {
      css: {
        modules: {
          localsConvention: "camelCase",
        },
      },
      // plugins: [eslint(), stylelint()],
    },
    inlineCSS: { relativeCSSInjection: true },
    minify: isProduction,
    createEmptyCSSEntryPoints: true,
    extractLicenseInformation: true,
    thirdPartyLicense: false,
  }
);
