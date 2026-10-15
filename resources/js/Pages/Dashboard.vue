
<script setup>
// ======================================================
// 1. IMPORTATION DES COMPOSANTS INERTIA
// ======================================================

import { Head, Link, router } from '@inertiajs/vue3'

// ======================================================
// 2. DONNÉES TRANSMISES PAR LARAVEL
// ======================================================

// Informations de l'utilisateur actuellement connecté.
defineProps({
  user: {
    type: Object,
    required: true,
  },
})

// ======================================================
// 3. DÉCONNEXION
// ======================================================

// Envoie une requête POST à Laravel pour déconnecter
// l'utilisateur et terminer sa session.
function logout() {
  router.post('/logout')
}
</script>

<template>
  <!-- Titre de la page dans l'onglet du navigateur -->
  <Head title="Dashboard — MessengerCustom" />

  <!-- ==================================================
       4. CONTENEUR PRINCIPAL
       ================================================== -->

  <div class="min-h-screen bg-slate-100 p-4 md:p-8">

    <div class="mx-auto max-w-4xl rounded-xl bg-white p-6 shadow md:p-8">

      <!-- ==================================================
           5. EN-TÊTE
           ================================================== -->

      <div class="mb-8 flex flex-wrap items-center justify-between gap-4">

        <div>
          <h1 class="text-3xl font-bold text-slate-800">
            Bienvenue sur MessengerCustom
          </h1>

          <p class="mt-2 text-slate-600">
            Tu es connecté à ton espace personnel.
          </p>
        </div>

        <!-- Bouton de déconnexion -->
        <button
            type="button"
            @click="logout"
            class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition hover:bg-red-700"
        >
          Se déconnecter
        </button>

      </div>

      <!-- ==================================================
           6. INFORMATIONS DE L'UTILISATEUR
           ================================================== -->

      <div class="mb-8 rounded-lg bg-blue-50 p-5">

        <h2 class="mb-4 text-xl font-semibold text-slate-800">
          Mon profil
        </h2>

        <p class="mb-2 text-slate-700">
          <strong>Nom :</strong>
          {{ user.display_name }}
        </p>

        <p class="text-slate-700">
          <strong>E-mail :</strong>
          {{ user.email }}
        </p>

      </div>

      <!-- ==================================================
           7. ACCÈS À LA MESSAGERIE
           ================================================== -->

      <div class="rounded-lg border border-slate-200 p-5">

        <h2 class="mb-2 text-xl font-semibold text-slate-800">
          Ma messagerie
        </h2>

        <p class="mb-5 text-sm text-slate-600">
          Accède à tes conversations privées, tes groupes
          et tes messages.
        </p>

        <!-- Navigation Inertia vers Conversations/Index.vue -->
        <Link
            href="/conversations"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700"
        >
          <!-- Icône messagerie -->
          <svg
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1.8"
              aria-hidden="true"
          >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M8 10h8M8 14h5m-1 7a9 9 0 1 0-9-9c0 1.6.42 3.1 1.16 4.4L3 21l4.6-1.16A9 9 0 0 0 12 21Z"
            />
          </svg>

          Ouvrir la messagerie
        </Link>

      </div>

    </div>

  </div>
</template>
