/**
 * ============================================================
 * MESSENGERCUSTOM — CONFIGURATION LARAVEL ECHO
 * ============================================================
 *
 * Laravel Echo permet au navigateur de recevoir
 * les événements diffusés par Laravel Reverb.
 *
 * Pusher JS est utilisé comme client WebSocket
 * compatible avec le protocole de Reverb.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Rendre le constructeur Pusher accessible à Echo.
window.Pusher = Pusher;

/**
 * Récupérer la configuration publique intégrée par Vite.
 *
 * IMPORTANT :
 * import.meta.env.VITE_* est évalué lors du build.
 * Ces variables doivent donc être disponibles
 * au moment de la compilation des ressources.
 */
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME;

/**
 * Initialiser Echo uniquement si les paramètres
 * indispensables sont présents.
 *
 * Cela évite qu'une configuration WebSocket incomplète
 * empêche le démarrage de Vue.js et d'Inertia.
 */
if (reverbKey && reverbHost) {

    const useTLS = reverbScheme === 'https';

    const reverbPort = Number(
        import.meta.env.VITE_REVERB_PORT
        || (useTLS ? 443 : 80)
    );

    // Création du client Echo.
    window.Echo = new Echo({
        broadcaster: 'reverb',

        // Clé publique de l'application Reverb.
        key: reverbKey,

        // Adresse du serveur WebSocket.
        wsHost: reverbHost,

        // Port des connexions WebSocket non chiffrées.
        wsPort: reverbPort,

        // Port des connexions WebSocket sécurisées.
        wssPort: reverbPort,

        // Utiliser WSS lorsque le protocole est HTTPS.
        forceTLS: useTLS,

        // Autoriser les transports WebSocket.
        enabledTransports: ['ws', 'wss'],
    });

} else {

    /**
     * Si Reverb n'est pas configuré :
     *
     * - ne pas créer de client Pusher ;
     * - laisser Vue.js démarrer normalement ;
     * - signaler que le temps réel est indisponible.
     *
     * Les autres fonctionnalités de l'application
     * restent accessibles.
     */
    window.Echo = null;

    console.warn(
        'MessengerCustom : Reverb non configuré. ' +
        'La messagerie en temps réel est désactivée.'
    );
}
