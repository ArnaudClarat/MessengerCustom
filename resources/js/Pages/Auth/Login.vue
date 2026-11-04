
<script setup>

/**
 * ============================================================
 * MESSENGERCUSTOM — PAGE DE CONNEXION
 * ============================================================
 *
 * Technologies :
 * - Vue.js 3 : interface utilisateur réactive
 * - Inertia.js : communication entre Vue et Laravel
 * - Laravel : traitement des requêtes et authentification
 * - Tailwind CSS : mise en forme de l'interface
 *
 * Le composant utilise la Composition API de Vue 3.
 *
 * <script setup> permet de déclarer directement les
 * variables et les fonctions utilisées dans le template.
 */

// ============================================================
// 1. IMPORTATION DES COMPOSANTS ET FONCTIONS INERTIA
// ============================================================

import { Head, Link, useForm } from '@inertiajs/vue3';

/**
 * Head :
 * Permet de modifier le titre HTML de la page.
 *
 * Link :
 * Composant de navigation Inertia.
 * Il permet de naviguer sans recharger entièrement la page.
 *
 * useForm :
 * Fonction fournie par Inertia pour gérer les formulaires.
 *
 * Elle permet notamment :
 * - de stocker les valeurs saisies ;
 * - d'envoyer les données à Laravel ;
 * - de récupérer les erreurs de validation ;
 * - de connaître l'état d'envoi du formulaire.
 */


// ============================================================
// 2. CRÉATION DU FORMULAIRE RÉACTIF
// ============================================================

/**
 * useForm() crée un objet réactif nommé form.
 *
 * Il contient deux champs :
 * - email : adresse e-mail de l'utilisateur ;
 * - password : mot de passe de l'utilisateur.
 *
 * Les valeurs sont initialisées à une chaîne vide.
 *
 * Lorsque l'utilisateur saisit du texte dans un input
 * utilisant v-model, la valeur correspondante est mise
 * à jour automatiquement.
 */
const form = useForm({
  email: '',
  password: '',
});


// ============================================================
// 3. SOUMISSION DU FORMULAIRE
// ============================================================

/**
 * Cette fonction est appelée lorsque l'utilisateur
 * clique sur le bouton "Se connecter".
 *
 * form.post('/login') envoie une requête HTTP POST
 * vers la route Laravel :
 *
 * POST /login
 *
 * Cette route appelle :
 * LoginController@login
 *
 * Inertia gère automatiquement :
 * - l'envoi des données ;
 * - les erreurs de validation ;
 * - l'état de chargement form.processing ;
 * - la navigation après une réponse appropriée.
 */
function submit() {
  form.post('/login');
}

</script>


<template>

  <!-- ======================================================
       4. TITRE HTML DE LA PAGE
       ====================================================== -->

  <!--
    Head modifie le titre de l'onglet du navigateur.

    Le titre affiché sera :
    Connexion — MessengerCustom
  -->
  <Head title="Connexion — MessengerCustom" />


  <!-- ======================================================
       5. CONTENEUR PRINCIPAL
       ====================================================== -->

  <!--
    flex :
    Active Flexbox.

    min-h-screen :
    Hauteur minimale égale à celle de l'écran.

    items-center :
    Centre verticalement les éléments sur l'axe secondaire.

    justify-center :
    Centre horizontalement les éléments sur l'axe principal.

    bg-slate-100 :
    Définit un arrière-plan gris clair.

    px-4 :
    Ajoute un padding horizontal de 1rem.
  -->
  <div
      class="flex min-h-screen items-center justify-center bg-slate-100 px-4"
  >

    <!-- ====================================================
         6. CARTE DE CONNEXION
         ==================================================== -->

    <!--
      w-full :
      Utilise toute la largeur disponible.

      max-w-md :
      Limite la largeur maximale à 28rem.

      rounded-xl :
      Arrondit les coins.

      bg-white :
      Arrière-plan blanc.

      p-8 :
      Padding intérieur de 2rem.

      shadow-lg :
      Ajoute une ombre à la carte.
    -->
    <div
        class="w-full max-w-md rounded-xl bg-white p-8 shadow-lg"
    >

      <!-- ================================================
           7. TITRE ET DESCRIPTION
           ================================================ -->

      <h1 class="mb-2 text-2xl font-bold text-slate-800">
        Connexion
      </h1>

      <p class="mb-6 text-sm text-slate-500">
        Connectez-vous à MessengerCustom.
      </p>


      <!-- ================================================
           8. LIEN VERS LA PAGE D'ACCUEIL
           ================================================ -->

      <!--
        Link est un composant Inertia.

        href="/" :
        Navigue vers la route d'accueil Laravel.

        Contrairement à une navigation HTML classique,
        Inertia peut effectuer cette navigation sans
        rechargement complet de la page.
      -->
      <Link
          href="/"
          class="text-sm text-blue-600 hover:underline"
      >
        ← Retour à l'accueil
      </Link>


      <!-- ================================================
           9. FORMULAIRE DE CONNEXION
           ================================================ -->

      <!--
        @submit :
        Écoute l'événement submit du formulaire.

        .prevent :
        Empêche le comportement HTML par défaut,
        qui provoquerait un rechargement classique.

        submit :
        Appelle la fonction définie dans <script setup>.

        space-y-5 :
        Ajoute un espacement vertical entre les enfants.
      -->
      <form
          @submit.prevent="submit"
          class="space-y-5"
      >

        <!-- ==============================================
             9.1. CHAMP ADRESSE E-MAIL
             ============================================== -->

        <div>

          <!--
            L'attribut for="email" associe le label
            à l'input possédant id="email".

            Cela améliore l'accessibilité.
          -->
          <label
              for="email"
              class="mb-1 block text-sm font-medium text-slate-700"
          >
            Adresse e-mail
          </label>

          <!--
            v-model="form.email" :
            Liaison bidirectionnelle entre l'input
            et la propriété form.email.

            type="email" :
            Active les contrôles HTML du format e-mail.

            autocomplete="username" :
            Permet au navigateur ou au gestionnaire
            de mots de passe de proposer l'identifiant.

            required :
            Rend le champ obligatoire côté navigateur.

            IMPORTANT :
            Laravel doit aussi valider l'e-mail côté serveur.
          -->
          <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
          />

          <!--
            v-if="form.errors.email" :
            Affiche le paragraphe uniquement lorsqu'une
            erreur de validation existe pour l'e-mail.

            {{ form.errors.email }} :
            Affiche le message d'erreur reçu via Inertia.
          -->
          <p
              v-if="form.errors.email"
              class="mt-1 text-sm text-red-600"
          >
            {{ form.errors.email }}
          </p>

        </div>


        <!-- ==============================================
             9.2. CHAMP MOT DE PASSE
             ============================================== -->

        <div>

          <label
              for="password"
              class="mb-1 block text-sm font-medium text-slate-700"
          >
            Mot de passe
          </label>

          <!--
            v-model="form.password" :
            Synchronise la saisie avec form.password.

            type="password" :
            Masque visuellement les caractères.

            autocomplete="current-password" :
            Indique au navigateur qu'il s'agit
            du mot de passe d'un compte existant.

            required :
            Empêche une soumission HTML normale
            lorsque le champ est vide.
          -->
          <input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
          />

          <!--
            Affichage conditionnel des erreurs
            associées au champ password.
          -->
          <p
              v-if="form.errors.password"
              class="mt-1 text-sm text-red-600"
          >
            {{ form.errors.password }}
          </p>

        </div>


        <!-- ==============================================
             9.3. BOUTON DE CONNEXION
             ============================================== -->

        <!--
          type="submit" :
          Soumet le formulaire.

          :disabled="form.processing" :
          Désactive le bouton pendant l'envoi.

          Les deux-points indiquent une liaison dynamique
          à une expression JavaScript.

          form.processing :
          Propriété gérée automatiquement par useForm.

          Elle vaut true pendant une requête en cours,
          puis false à sa fin.

          disabled:opacity-50 :
          Réduit l'opacité du bouton lorsqu'il est désactivé.
        -->
        <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
        >

          <!--
            Opérateur ternaire JavaScript :

            condition ? valeurSiVrai : valeurSiFaux

            Si form.processing est true :
            "Connexion..."

            Sinon :
            "Se connecter"
          -->
          {{ form.processing ? 'Connexion...' : 'Se connecter' }}

        </button>

      </form>


      <!-- ==================================================
           10. LIEN VERS L'INSCRIPTION
           ================================================== -->

      <!--
        Permet à un visiteur qui ne possède pas encore
        de compte d'accéder au formulaire d'inscription.

        Route Laravel :
        GET /register
      -->
      <p class="mt-5 text-center text-sm text-slate-600">

        Pas encore de compte ?

        <Link
            href="/register"
            class="font-semibold text-indigo-600 hover:underline"
        >
          Créer un compte
        </Link>

      </p>


      <!-- ==================================================
           11. SÉPARATEUR VISUEL
           ================================================== -->

      <!--
        my-6 :
        Marge verticale de 1.5rem.

        flex :
        Active Flexbox.

        items-center :
        Centre les éléments verticalement.

        gap-3 :
        Ajoute un espace entre les éléments.

        flex-1 :
        Permet aux lignes horizontales
        d'occuper l'espace disponible.

        h-px :
        Hauteur de 1 pixel.
      -->
      <div class="my-6 flex items-center gap-3">

        <div class="h-px flex-1 bg-slate-200"></div>

        <span class="text-sm text-slate-500">
          Ou continuer avec
        </span>

        <div class="h-px flex-1 bg-slate-200"></div>

      </div>


      <!-- ==================================================
           12. AUTHENTIFICATION OAUTH
           ================================================== -->

      <!--
        Les liens OAuth utilisent <a> plutôt que <Link>.

        Pourquoi ?

        L'authentification OAuth implique généralement
        une redirection HTTP complète vers un site externe.

        Un lien HTML classique convient à ce scénario.

        Laravel reçoit d'abord la requête sur :
        /auth/{provider}/redirect

        OAuthController@redirect prépare ensuite
        la redirection vers le fournisseur.
      -->
      <div class="space-y-3">

        <!-- ================================================
             12.1. CONNEXION MICROSOFT
             ================================================ -->

        <a
            href="/auth/microsoft/redirect"
            class="block w-full rounded-lg border border-slate-300 px-4 py-2 text-center font-medium text-slate-700 hover:bg-slate-50"
        >
          Continuer avec Microsoft
        </a>


        <!-- ================================================
             12.2. CONNEXION GOOGLE
             ================================================ -->

        <a
            href="/auth/google/redirect"
            class="block w-full rounded-lg border border-slate-300 px-4 py-2 text-center font-medium text-slate-700 hover:bg-slate-50"
        >
          Continuer avec Google
        </a>


        <!-- ================================================
             12.3. CONNEXION GITHUB
             ================================================ -->

        <a
            href="/auth/github/redirect"
            class="block w-full rounded-lg border border-slate-300 px-4 py-2 text-center font-medium text-slate-700 hover:bg-slate-50"
        >
          Continuer avec GitHub
        </a>

      </div>

    </div>

  </div>

</template>
