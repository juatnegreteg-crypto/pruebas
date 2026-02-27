import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import prettier from 'eslint-config-prettier';
import importPlugin from 'eslint-plugin-import';
import vue from 'eslint-plugin-vue';
import i18nNoHardcoded from './eslint-rules/i18n-no-hardcoded.js';

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        ignores: [
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'packages',
            'storage',
            'tailwind.config.js',
            'vite.config.ts',
            'resources/js/actions',
            'resources/js/routes',
            'resources/js/wayfinder',
            'resources/js/components/ui/**',
        ],
    },
    {
        plugins: {
            import: importPlugin,
            local: {
                rules: {
                    'i18n-no-hardcoded': i18nNoHardcoded,
                },
            },
        },
        settings: {
            'import/resolver': {
                typescript: {
                    alwaysTryTypes: true,
                    project: './tsconfig.json',
                },
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/consistent-type-imports': [
                'error',
                {
                    prefer: 'type-imports',
                    fixStyle: 'separate-type-imports',
                },
            ],
            'import/order': [
                'error',
                {
                    groups: [
                        'builtin',
                        'external',
                        'internal',
                        'parent',
                        'sibling',
                        'index',
                    ],
                    pathGroups: [
                        {
                            pattern: '@/types{,/**}',
                            group: 'internal',
                            position: 'before',
                        },
                        {
                            pattern: '@/routes{,/**}',
                            group: 'internal',
                            position: 'after',
                        },
                        {
                            pattern: '@/actions{,/**}',
                            group: 'internal',
                            position: 'after',
                        },
                    ],
                    pathGroupsExcludedImportTypes: ['builtin', 'external', 'type'],
                    alphabetize: {
                        order: 'asc',
                        caseInsensitive: true,
                    },
                },
            ],
        },
    },
    {
        files: [
            'resources/js/components/CustomerForm.vue',
            'resources/js/components/PartyAddressList.vue',
            'resources/js/components/ProductForm.vue',
            'resources/js/pages/customers/**/*.vue',
        ],
        rules: {
            'local/i18n-no-hardcoded': 'error',
        },
    },
    prettier,
);
