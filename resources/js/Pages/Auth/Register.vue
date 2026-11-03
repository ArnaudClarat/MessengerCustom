<script setup>
// useForm centralise les valeurs, erreurs et l'état de soumission Inertia.
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({
  display_name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  // La route POST /register effectue la validation côté Laravel.
  form.post('/register', {
    // Effacer les mots de passe du navigateur après la réponse.
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <Head title="Inscription — MessengerCustom" />
  <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-8">
    <div class="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
      <h1 class="mb-2 text-2xl font-bold text-slate-800">Créer un compte</h1>
      <p class="mb-6 text-sm text-slate-500">Rejoignez MessengerCustom.</p>

      <!-- prevent empêche le rechargement classique de la page. -->
      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Nom complet</label>
          <input id="display_name" v-model="form.display_name" type="text" autocomplete="name" maxlength="100" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none" />
          <p v-if="form.errors.display_name" role="alert" class="mt-1 text-sm text-red-600">{{ form.errors.display_name }}</p>
        </div>

        <div>
          <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Adresse e-mail</label>
          <input id="email" v-model="form.email" type="email" autocomplete="email" maxlength="255" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none" />
          <p v-if="form.errors.email" role="alert" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
          <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Mot de passe (12 caractères minimum)</label>
          <input id="password" v-model="form.password" type="password" autocomplete="new-password" minlength="12" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none" />
          <p v-if="form.errors.password" role="alert" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>

        <div>
          <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
          <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="12" required
                 class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none" />
        </div>

        <button type="submit" :disabled="form.processing"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
          {{ form.processing ? 'Création en cours...' : 'Créer mon compte' }}
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-slate-600">
        Déjà inscrit ? <Link href="/login" class="font-medium text-indigo-600 hover:underline">Se connecter</Link>
      </p>
      <p class="mt-3 text-center text-sm"><Link href="/" class="text-slate-500 hover:underline">Retour à l'accueil</Link></p>
    </div>
  </main>
</template>
