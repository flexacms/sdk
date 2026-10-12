import { defineConfig } from 'tsup';

/**
 * The module entry plus its runtime files, which Nuxt loads by path from
 * dist/runtime. Virtual Nuxt imports stay external.
 */
export default defineConfig({
    entry: {
        module: 'src/module.ts',
        'runtime/composables': 'src/runtime/composables.ts',
        'runtime/server/proxy': 'src/runtime/server/proxy.ts',
        'runtime/server/client': 'src/runtime/server/client.ts',
        'runtime/proxy-path': 'src/runtime/proxy-path.ts',
    },
    format: ['esm'],
    dts: { entry: { module: 'src/module.ts' } },
    clean: true,
    target: 'es2022',
    external: [
        '#imports',
        'h3',
        'vue',
        '@nuxt/kit',
        '@nuxt/schema',
        'defu',
        '@flexacms/client',
    ],
});
