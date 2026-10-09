import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Port 4000 dilarang untuk dev server spektrumX — jangan dihidupkan lagi.
const BLOCKED_PORT = 4000;

const blockPort = {
    name: 'spektrumx-block-port-4000',
    configureServer(server) {
        const port = Number(server.config.server.port);
        if (port === BLOCKED_PORT) {
            console.error(`\n[spektrumX] Vite di port ${BLOCKED_PORT} diblokir. Pakai "npm run build".\n`);
            process.exit(1);
        }
    },
};

export default defineConfig({
    plugins: [
        blockPort,
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/avatar-editor.js'],
            refresh: true,
        }),
    ],
});
