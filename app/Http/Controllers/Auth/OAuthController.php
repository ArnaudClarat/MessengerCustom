<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthController extends Controller
{
    /**
     * Liste des fournisseurs OAuth autorisés.
     */
    private const PROVIDERS = [
        'microsoft',
        'google',
        'github',
    ];

    /**
     * Redirige vers le fournisseur OAuth choisi.
     */
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(
            in_array($provider, self::PROVIDERS, true),
            404
        );

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Traite le retour du fournisseur OAuth.
     */
    public function callback(
        Request $request,
        string $provider
    ): RedirectResponse {
        abort_unless(
            in_array($provider, self::PROVIDERS, true),
            404
        );

        // La liaison doit avoir été demandée explicitement.
        $linkingProvider = $request->session()->get('oauth_link_provider');
        $linkingUserId = $request->session()->get('oauth_link_user_id');

        if ($linkingProvider !== null) {
            if (
                $linkingProvider !== $provider
                || ! Auth::check()
                || Auth::id() !== $linkingUserId
            ) {
                $request->session()->forget([
                    'oauth_link_provider',
                    'oauth_link_user_id',
                ]);

                return redirect()->route('login')
                    ->withErrors([
                        'email' => 'La demande de liaison du compte a expiré ou est invalide.',
                    ]);
            }
        }

        try {
            $externalUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            Log::warning('Échec de l’authentification OAuth.', [
                'provider' => $provider,
                'exception' => $exception::class,
            ]);

            $request->session()->forget([
                'oauth_link_provider',
                'oauth_link_user_id',
            ]);

            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Impossible de vérifier votre identité auprès du fournisseur.',
                ]);
        }

        $identity = AuthIdentity::query()
            ->where('provider', $provider)
            ->where('provider_user_id', (string) $externalUser->getId())
            ->first();

        // Cas 1 : l'utilisateur demande à lier un compte externe.
        if ($linkingProvider !== null) {
            $request->session()->forget([
                'oauth_link_provider',
                'oauth_link_user_id',
            ]);

            if ($identity !== null) {
                if ((string) $identity->user_id === (string) Auth::id()) {
                    return redirect()->route('dashboard')
                        ->with('success', 'Ce compte externe est déjà lié à votre compte.');
                }

                return redirect()->route('dashboard')
                    ->withErrors([
                        'oauth' => 'Ce compte externe est déjà lié à un autre utilisateur.',
                    ]);
            }

            // Aucune insertion en base dans cette première version.
            return redirect()->route('dashboard')
                ->with('info', 'Identité vérifiée. La liaison sera activée dans une étape ultérieure.');
        }

        // Cas 2 : connexion normale.
        if ($identity === null) {
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Aucun compte MessengerCustom n’est associé à cette identité. Connectez-vous avec votre compte existant, puis demandez explicitement la liaison.',
                ]);
        }

        Auth::login($identity->user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Démarre une demande explicite de liaison de compte.
     */
    public function link(
        Request $request,
        string $provider
    ): RedirectResponse {
        abort_unless(
            in_array($provider, self::PROVIDERS, true),
            404
        );

        $request->session()->put([
            'oauth_link_provider' => $provider,
            'oauth_link_user_id' => (string) Auth::id(),
        ]);

        return Socialite::driver($provider)->redirect();
    }
}
