import { defineConfig } from "vite";
import { resolve } from "path";

export default defineConfig({
    root: ".",
    publicDir: false, // static files are served directly by Apache; no copy needed
    build: {
        outDir: "public/dist",
        emptyOutDir: true,
        manifest: "manifest.json",
        rollupOptions: {
            input: {
                app: resolve(__dirname, "src/app.js"),
            },
            // jQuery loaded globally from public/js/jquery.js — keep as external
            external: [],
        },
        // Minify JS and CSS
        minify: "esbuild",
        cssMinify: true,
        sourcemap: false,
    },
    css: {
        devSourcemap: false,
    },
});
