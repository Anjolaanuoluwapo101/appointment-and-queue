import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Runtime config (injected by Blade) wins so one image works on any
// host; otherwise fall back to the Vite build-time variables.
const runtime = window.__APP_CONFIG__ ?? {};
const buildtime = import.meta.env;

const echo = new Echo({
    broadcaster: 'reverb',
    key: runtime.reverbKey ?? buildtime.VITE_REVERB_APP_KEY,
    wsHost: runtime.reverbHost ?? buildtime.VITE_REVERB_HOST,
    wsPort: Number(runtime.reverbPort ?? buildtime.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(runtime.reverbPort ?? buildtime.VITE_REVERB_PORT ?? 8080),
    forceTLS: (runtime.reverbScheme ?? buildtime.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
});

export default echo;
