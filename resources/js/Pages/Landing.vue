
<script setup>
import { Head, Link } from '@inertiajs/vue3'

const features = [
  {
    icon: '💬',
    title: 'Messagerie collaborative',
    description: 'Centralisez les échanges professionnels dans un espace commun.',
  },
  {
    icon: '👥',
    title: 'Travail en équipe',
    description: 'Organisez les discussions entre collègues et collaborateurs.',
  },
  {
    icon: '🔐',
    title: 'Accès sécurisés',
    description: 'Chaque utilisateur accède aux conversations auxquelles il participe.',
  },
]

const plans = [
  {
    name: 'Free',
    price: '0 €',
    description: 'Pour découvrir MessengerCustom.',
    popular: false,
    benefits: [
      'Jusqu’à 3 membres',
      '1 000 messages par mois',
      '100 Mo de pièces jointes',
      'Rétention de 30 jours',
      'Conversations privées',
    ],
  },
  {
    name: 'Pro',
    price: '6 €',
    description: 'Pour les équipes qui veulent aller plus loin.',
    popular: true,
    benefits: [
      'Membres illimités',
      'Messages illimités',
      '10 Go de pièces jointes par membre',
      'Rétention illimitée',
      'Conversations et groupes',
      'Gestion avancée des accès',
    ],
  },
]
</script>

<template>
  <Head title="MessengerCustom — Messagerie collaborative" />

  <div class="min-h-screen bg-slate-50 text-slate-900">
    <!-- Navigation -->
    <header class="border-b border-slate-200 bg-white">
      <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">

        <Link href="/" class="text-xl font-bold text-blue-700">
          MessengerCustom
        </Link>

        <div class="flex items-center gap-5">
          <Link href="/" class="hover:text-blue-600">
            Accueil
          </Link>

          <a href="#fonctionnalites" class="hover:text-blue-600">
            Fonctionnalités
          </a>

          <a href="#tarifs" class="hover:text-blue-600">
            Tarifs
          </a>

          <Link href="/login" class="hover:text-blue-600">
            Connexion
          </Link>

          <Link
              href="/login"
              class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
          >
            Essayer maintenant
          </Link>
        </div>

      </nav>
    </header>

    <main>
      <!-- Présentation principale -->
      <section class="bg-slate-950 px-6 py-24 text-white">
        <div class="mx-auto max-w-5xl text-center">
          <p class="mb-5 font-semibold tracking-widest text-blue-400">
            MESSAGERIE COLLABORATIVE B2B
          </p>

          <h1 class="text-4xl font-extrabold leading-tight md:text-6xl">
            Communiquez mieux.
            <span class="text-blue-400">Collaborez plus vite.</span>
          </h1>

          <p class="mx-auto mt-8 max-w-2xl text-lg text-slate-300">
            MessengerCustom rassemble les conversations de votre équipe
            dans une interface simple et professionnelle.
          </p>

          <div class="mt-10 flex flex-wrap justify-center gap-4">
            <Link
                href="/login"
                class="rounded-xl bg-blue-600 px-8 py-4 font-semibold text-white hover:bg-blue-700"
            >
              Essayer maintenant →
            </Link>

            <a
                href="#tarifs"
                class="rounded-xl border border-slate-500 px-8 py-4 font-semibold hover:bg-slate-800"
            >
              Voir les tarifs
            </a>
          </div>

          <!-- Aperçu visuel -->
          <div class="mx-auto mt-16 max-w-4xl rounded-2xl border border-slate-700 bg-slate-900 p-4 shadow-2xl">
            <div class="grid min-h-64 grid-cols-3 gap-3 text-left">
              <aside class="rounded-xl bg-slate-800 p-4">
                <p class="mb-5 text-sm font-bold text-blue-300">
                  Conversations
                </p>
                <p class="mb-4 rounded-lg bg-slate-700 p-3 text-sm">
                  👥 Équipe projet
                </p>
                <p class="rounded-lg p-3 text-sm text-slate-300">
                  💬 Discussion privée
                </p>
              </aside>

              <div class="col-span-2 rounded-xl bg-slate-800 p-4">
                <p class="border-b border-slate-600 pb-3 font-semibold">
                  Équipe projet
                </p>

                <div class="mt-6 flex justify-start">
                  <span class="rounded-xl bg-slate-700 px-4 py-3 text-sm">
                    Bonjour, où en est le projet ?
                  </span>
                </div>

                <div class="mt-4 flex justify-end">
                  <span class="rounded-xl bg-blue-600 px-4 py-3 text-sm">
                    Tout avance comme prévu !
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Fonctionnalités -->
      <section id="fonctionnalites" class="px-6 py-24">
        <div class="mx-auto max-w-7xl">
          <h2 class="text-center text-3xl font-bold md:text-4xl">
            Tout pour simplifier vos échanges
          </h2>

          <p class="mt-4 text-center text-slate-600">
            Une plateforme pensée pour la communication professionnelle.
          </p>

          <div class="mt-14 grid gap-8 md:grid-cols-3">
            <article
                v-for="feature in features"
                :key="feature.title"
                class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
            >
              <div class="mb-5 text-4xl">{{ feature.icon }}</div>
              <h3 class="mb-3 text-xl font-bold">{{ feature.title }}</h3>
              <p class="leading-relaxed text-slate-600">
                {{ feature.description }}
              </p>
            </article>
          </div>
        </div>
      </section>

      <!-- Tarification -->
      <section id="tarifs" class="bg-slate-100 px-6 py-24">
        <div class="mx-auto max-w-5xl">
          <h2 class="text-center text-3xl font-bold md:text-4xl">
            Des tarifs simples et transparents
          </h2>

          <p class="mt-4 text-center text-slate-600">
            Commencez gratuitement et évoluez selon vos besoins.
          </p>

          <div class="mt-14 grid gap-8 md:grid-cols-2">
            <article
                v-for="plan in plans"
                :key="plan.name"
                class="relative flex flex-col rounded-2xl border bg-white p-8 shadow-sm"
                :class="plan.popular ? 'border-2 border-blue-600' : 'border-slate-200'"
            >
              <span
                  v-if="plan.popular"
                  class="absolute -top-4 right-6 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
              >
                Pour les équipes
              </span>

              <h3 class="text-2xl font-bold">{{ plan.name }}</h3>
              <p class="mt-3 text-slate-600">{{ plan.description }}</p>

              <div class="mt-8">
                <span class="text-5xl font-extrabold">{{ plan.price }}</span>
                <span class="text-slate-500">
                  {{ plan.popular ? '/ personne / mois' : '/ mois' }}
                </span>
              </div>

              <ul class="mt-10 flex-1 space-y-5">
                <li
                    v-for="benefit in plan.benefits"
                    :key="benefit"
                    class="flex items-start gap-3"
                >
                  <span class="font-bold text-green-600">✓</span>
                  <span>{{ benefit }}</span>
                </li>
              </ul>

              <Link
                  href="/login"
                  class="mt-10 block rounded-xl px-6 py-4 text-center font-semibold"
                  :class="
                  plan.popular
                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                    : 'bg-slate-900 text-white hover:bg-slate-800'
                "
              >
                {{ plan.popular ? 'Choisir Pro' : 'Commencer gratuitement' }}
              </Link>
            </article>
          </div>

          <p class="mt-8 text-center text-sm text-slate-500">
            Quotas présentés à titre indicatif, sous réserve de leur implémentation.
          </p>
        </div>
      </section>

      <!-- Dernier appel à l'action -->
      <section class="bg-blue-700 px-6 py-20 text-center text-white">
        <h2 class="text-3xl font-bold">
          Prêt à simplifier la communication de votre équipe ?
        </h2>

        <p class="mt-5 text-blue-100">
          Découvrez MessengerCustom dès aujourd'hui.
        </p>

        <Link
            href="/login"
            class="mt-8 inline-block rounded-xl bg-white px-8 py-4 font-bold text-blue-700 hover:bg-blue-50"
        >
          Essayer maintenant →
        </Link>
      </section>
    </main>

    <!-- Pied de page -->
    <footer class="bg-slate-950 px-6 py-8 text-center text-slate-400">
      <p>© {{ new Date().getFullYear() }} MessengerCustom.</p>
    </footer>
  </div>
</template>
