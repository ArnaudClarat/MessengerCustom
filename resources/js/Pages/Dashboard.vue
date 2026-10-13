<script setup>
import { router } from '@inertiajs/vue3';

// Informations transmises par Laravel.
defineProps({
  user: {
    type: Object,
    required: true,
  },
});

// Déconnecte l'utilisateur.
function logout() {
  router.post('/logout');
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 p-8">
    <div class="mx-auto max-w-4xl rounded-xl bg-white p-8 shadow">

      <h1 class="mb-4 text-3xl font-bold text-slate-800">
        Bienvenue sur MessengerCustom
      </h1>

      <p class="mb-6 text-slate-600">
        Tu es connecté à ton espace personnel.
      </p>

      <div class="mb-6 rounded-lg bg-blue-50 p-4">
        <h2 class="mb-3 text-xl font-semibold">
          Mon profil
        </h2>

        <p>
          <strong>Nom :</strong>
          {{ user.display_name }}
        </p>

        <p>
          <strong>E-mail :</strong>
          {{ user.email }}
        </p>
      </div>

      <button
          @click="logout"
          class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700"
      >
        Se déconnecter
      </button>

    </div>
  </div>
</template>
