import { resolve } from 'node:path';
import { HstVue } from '@histoire/plugin-vue';
import { defineConfig } from 'histoire';
import tsconfigPaths from 'vite-tsconfig-paths';

export default defineConfig({
    plugins: [HstVue()],
    storyMatch: ['resources/js/components/ui/**/*.story.vue'],
    setupFile: './resources/js/histoire.setup.ts',
    vite: {
        resolve: {
            alias: {
                '@': resolve(__dirname, 'resources/js'),
                '#': resolve(__dirname, 'resources'),
            },
        },
        plugins: [tsconfigPaths()],
    },
});
