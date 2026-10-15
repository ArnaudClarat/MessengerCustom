<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
});

function submit() {
  form.post('/login');
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
    <div class="w-full max-w-md rounded-xl bg-white p-8 shadow-lg">
      <h1 class="mb-2 text-2xl font-bold text-slate-800">
        Connexion
      </h1>

      <p class="mb-6 text-sm text-slate-500">
        Connectez-vous à MessengerCustom.
      </p>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="email" class="mb-1 block text-sm font-medium text-slate-700">
            Adresse e-mail
          </label>

          <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
          />

          <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
            {{ form.errors.email }}
          </p>
        </div>

        <div>
          <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
            Mot de passe
          </label>

          <input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none"
          />

          <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
            {{ form.errors.password }}
          </p>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ form.processing ? 'Connexion...' : 'Se connecter' }}
        </button>
      </form>

      <div class="my-6 flex items-center gap-3">
        <div class="h-px flex-1 bg-slate-200"></div>
        <span class="text-sm text-slate-500">Ou continuer avec</span>
        <div class="h-px flex-1 bg-slate-200"></div>
      </div>

      <div class="space-y-3">
        <a
            href="/auth/microsoft/redirect"
            class="block w-full rounded-lg border border-slate-300 px-4 py-2 text-center font-medium text-slate-700 hover:bg-slate-50"
        >
          Continuer avec Microsoft
        </a>

        <a
            href="/auth/google/redirect"
            class="block w-full rounded-lg border border-slate-300 px-4 py-2 text-center font-medium text-slate-700 hover:bg-slate-50"
        >
          Continuer avec Google
        </a>

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
