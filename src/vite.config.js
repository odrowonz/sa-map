import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/njk-editor.js',
                'resources/js/project-export-wizard.js',
                'resources/js/project-md-export.js',
            ],
            refresh: true,
        }),
    ],
});
