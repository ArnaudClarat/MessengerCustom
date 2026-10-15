<script setup>

// ======================================================
// 1. IMPORTATION DES OUTILS VUE ET INERTIA
// ======================================================

import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'


// ======================================================
// 2. DONNÉES FOURNIES PAR LARAVEL / INERTIA
// ======================================================

// Le contrôleur transmet l'utilisateur authentifié et les
// conversations préparées pour l'affichage. Aucune donnée fictive.
const props = defineProps({
  user: { type: Object, required: true },
  conversationsFromDb: { type: Array, default: () => [] },
})

// computed reste synchronisé avec les nouvelles props Inertia.
// Le contrôleur Laravel doit fournir name, type, members et messages.
const conversations = computed(() => props.conversationsFromDb)


// ======================================================
// 3. VARIABLES RÉACTIVES
// ======================================================

// Contient le texte saisi dans la barre de recherche.
const search = ref('')

// Identifiant de la conversation sélectionnée.
// Sélectionner la première conversation si elle existe.
// Sur mobile, le bouton Retour permet d'afficher la liste.
const selectedId = ref(conversations.value[0]?.id ?? null)

// Prévu pour la saisie lors de l'activation future de l'envoi.
const newMessage = ref('')

// Lors d'une actualisation Inertia, conserver la conversation ouverte
// si elle existe encore. Si elle a disparu, sélectionner la première.
// Une sélection null (retour à la liste sur mobile) reste volontairement null.
watch(conversations, (items) => {
  if (selectedId.value === null) return
  if (!items.some(c => c.id === selectedId.value)) {
    selectedId.value = items[0]?.id ?? null
  }
})


// ======================================================
// 4. RÉCUPÉRER LE DERNIER MESSAGE
// ======================================================

function lastMessage(conversation) {
  const messages = conversation.messages ?? []
  return messages.length > 0 ? messages[messages.length - 1] : null
}


// ======================================================
// 5. FORMATER LES DATES
// ======================================================

// Dans la liste des conversations :
// - aujourd'hui → heure seule (ex. : 09:35)
// - autre jour  → date courte (ex. : 11/10)

function formatDate(date) {
  if (!date) return ''
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return ''

  const isToday = parsed.toDateString() === new Date().toDateString()

  if (isToday) {
    return parsed.toLocaleString('fr-BE', { hour: '2-digit', minute: '2-digit' })
  }

  return parsed.toLocaleString('fr-BE', { day: '2-digit', month: '2-digit' })
}

// Dans les bulles : toujours afficher la date et l'heure.

function formatMessageDate(date) {
  if (!date) return ''
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return ''

  return parsed.toLocaleString('fr-BE', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}


// ======================================================
// 6. AVATARS : INITIALES ET COULEUR
// ======================================================

// Extrait les initiales d'un nom complet.
// Exemple : 'Marie Dupont' → 'MD'

function getInitials(name) {
  if (!name) return '?'
  return name
      .split(' ')
      .slice(0, 2)
      .map(part => part[0]?.toUpperCase() ?? '')
      .join('')
}

// Couleurs utilisées pour les avatars.
// On utilise des styles en ligne plutôt que des classes
// dynamiques, car Tailwind ne détecte pas les classes
// construites à l'exécution.

const AVATAR_COLORS = [
  '#4f46e5', // indigo
  '#0284c7', // sky
  '#059669', // emerald
  '#d97706', // amber
  '#e11d48', // rose
  '#0891b2', // cyan
  '#7c3aed', // violet
  '#0d9488', // teal
]

// Retourne un objet { backgroundColor } basé sur le
// nom, pour être passé à l'attribut :style.

function avatarStyle(name) {
  if (!name) return { backgroundColor: AVATAR_COLORS[0] }
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = (hash * 31 + name.charCodeAt(i)) & 0xffff
  }
  return { backgroundColor: AVATAR_COLORS[hash % AVATAR_COLORS.length] }
}


// ======================================================
// 7. FILTRER ET TRIER LES CONVERSATIONS
// ======================================================

const filteredConversations = computed(() => {
  const keyword = search.value.trim().toLocaleLowerCase()

  return conversations.value
      .filter(conversation => {
        if (!keyword) return true

        const nameMatches = (conversation.name ?? '')
            .toLocaleLowerCase()
            .includes(keyword)

        const messageMatches = (conversation.messages ?? [])
            .some(message =>
                (message.text ?? '').toLocaleLowerCase().includes(keyword)
            )

        return nameMatches || messageMatches
      })
      .sort((a, b) => {
        const dateA = lastMessage(a)?.date ?? ''
        const dateB = lastMessage(b)?.date ?? ''
        return dateB.localeCompare(dateA)
      })
})

// Sous-listes par type, pour les sections de la barre latérale.

const directConversations = computed(() =>
    filteredConversations.value.filter(c => c.type === 'direct')
)

const groupConversations = computed(() =>
    filteredConversations.value.filter(c => c.type === 'group')
)

const channelConversations = computed(() =>
    filteredConversations.value.filter(c => c.type === 'channel')
)


// ======================================================
// 8. CONVERSATION ACTUELLEMENT SÉLECTIONNÉE
// ======================================================

const selectedConversation = computed(() =>
    conversations.value.find(c => c.id === selectedId.value) ?? null
)


// ======================================================
// 9. OUVRIR UNE CONVERSATION
// ======================================================

function selectConversation(conversationId) {
  selectedId.value = conversationId
  newMessage.value = ''
}


// ======================================================
// 10. AFFICHER LE TYPE DE CONVERSATION
// ======================================================

function conversationTypeLabel(type) {
  switch (type) {
    case 'direct':  return 'Message direct'
    case 'group':   return 'Groupe'
    case 'channel': return 'Canal'
    default:        return 'Conversation'
  }
}


// ======================================================
// 11. ENVOI RÉEL DES MESSAGES
// ======================================================

// Cette fonction envoie le message au contrôleur Laravel.
// Laravel vérifie les autorisations et enregistre le message
// dans PostgreSQL.

function sendMessage() {

  // Récupérer le message sans les espaces inutiles.
  const content = newMessage.value.trim()

  // Ne rien envoyer si le message est vide ou si
  // aucune conversation n'est sélectionnée.
  if (!content || !selectedConversation.value) {
    return
  }

  // Envoyer une requête POST à Laravel via Inertia.
  router.post(
      `/conversations/${selectedConversation.value.id}/messages`,

      // Données envoyées au serveur.
      { content },

      {
        // Conserver la position de défilement.
        preserveScroll: true,

        // Vider le champ après un envoi réussi.
        onSuccess: () => {
          newMessage.value = ''
        },
      }
  )
}

// ======================================================
// 12. DÉCONNEXION DE L'UTILISATEUR
// ======================================================

function logout() {
  router.post('/logout')
}

</script>


<template>

  <Head title="Conversations — MessengerCustom" />

  <!-- Conteneur principal : occupe toute la hauteur de l'écran -->
  <div class="flex h-screen flex-col overflow-hidden bg-slate-100 font-sans">


    <!-- ==================================================
         13. EN-TÊTE DE L'APPLICATION
         ================================================== -->

    <header class="flex shrink-0 items-center justify-between bg-slate-900 px-6 py-3 shadow-md">

      <!-- Logo et nom -->
      <div class="flex items-center gap-3">
        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-500 text-sm font-bold text-white">
          MC
        </div>
        <span class="text-base font-semibold tracking-wide text-white">
          MessengerCustom
        </span>
        <span class="hidden rounded-full bg-slate-700 px-2.5 py-0.5 text-xs text-slate-300 sm:inline">
          Entreprise
        </span>
      </div>

      <!-- Zone utilisateur et navigation principale -->
      <div class="flex items-center gap-2 sm:gap-4">

        <!-- Lien Inertia : retour au tableau de bord sans rechargement complet. -->
        <Link
            href="/dashboard"
            class="rounded-md border border-slate-700 bg-slate-800 px-3 py-1.5 text-sm text-slate-300 transition hover:bg-slate-700 hover:text-white sm:px-4"
        >
          Dashboard
        </Link>

        <!-- Nom et avatar de l'utilisateur connecté -->
        <div class="hidden items-center gap-2 sm:flex">
          <div
              class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold text-white"
              style="background-color: #4f46e5"
          >
            {{ getInitials(user.display_name) }}
          </div>
          <span class="text-sm text-slate-300">{{ user.display_name }}</span>
        </div>

        <button
            type="button"
            @click="logout"
            class="rounded-md border border-slate-700 bg-slate-800 px-4 py-1.5 text-sm text-slate-300 transition hover:bg-slate-700 hover:text-white"
        >
          Déconnexion
        </button>

      </div>

    </header>


    <!-- ==================================================
         14. CONTENU PRINCIPAL
         ================================================== -->

    <!--
      Sur mobile  : une seule colonne — liste OU messages (jamais les deux).
      À partir de md : barre latérale (1 col) + zone messages (2 cols).

      min-h-0 est nécessaire pour que les enfants flex
      puissent défiler indépendamment sans déborder.
    -->

    <main class="grid min-h-0 flex-1 grid-cols-1 md:grid-cols-3">


      <!-- ================================================
           15. BARRE LATÉRALE — LISTE DES CONVERSATIONS
           ================================================ -->

      <!--
        Sur mobile : visible uniquement quand aucune conversation
        n'est ouverte (selectedId === null).
        Sur desktop (md+) : toujours visible via md:flex.
      -->
      <aside
          class="min-h-0 flex-col overflow-hidden border-r border-slate-200 bg-white"
          :class="selectedId !== null ? 'hidden md:flex' : 'flex'"
      >

        <!-- En-tête de la barre latérale -->
        <div class="shrink-0 border-b border-slate-100 px-4 pb-3 pt-4">

          <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">
            Conversations
          </h2>

          <!-- Barre de recherche avec icône -->
          <div class="relative">
            <svg
                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" />
            </svg>
            <input
                v-model="search"
                type="search"
                placeholder="Rechercher..."
                class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
            />
          </div>

        </div>


        <!-- Liste des conversations (scrollable) -->
        <div class="flex-1 overflow-y-auto py-2">


          <!-- Section : Messages directs -->
          <template v-if="directConversations.length > 0">

            <p class="mb-1 mt-3 px-4 text-xs font-semibold uppercase tracking-widest text-slate-400">
              Messages directs
              <span class="ml-1 font-normal text-slate-300">
                {{ directConversations.length }}
              </span>
            </p>

            <button
                v-for="conversation in directConversations"
                :key="conversation.id"
                type="button"
                @click="selectConversation(conversation.id)"
                class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition"
                :class="
                selectedId === conversation.id
                  ? 'bg-indigo-50'
                  : 'hover:bg-slate-50'
              "
            >

              <!-- Avatar du participant (présence non implémentée) -->
              <div class="relative shrink-0">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold text-white"
                    :style="avatarStyle(conversation.name)"
                >
                  {{ getInitials(conversation.name) }}
                </div>

              </div>

              <!-- Nom, date et aperçu -->
              <div class="min-w-0 flex-1">
                <div class="flex items-baseline justify-between gap-1">
                  <span
                      class="truncate text-sm font-semibold"
                      :class="selectedId === conversation.id ? 'text-indigo-700' : 'text-slate-800'"
                  >
                    {{ conversation.name }}
                  </span>
                  <span class="shrink-0 text-xs text-slate-400">
                    {{ formatDate(lastMessage(conversation)?.date) }}
                  </span>
                </div>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                  {{ lastMessage(conversation)?.text || 'Aucun message' }}
                </p>
              </div>

            </button>

          </template>


          <!-- Section : Groupes -->
          <template v-if="groupConversations.length > 0">

            <p class="mb-1 mt-4 px-4 text-xs font-semibold uppercase tracking-widest text-slate-400">
              Groupes
              <span class="ml-1 font-normal text-slate-300">
                {{ groupConversations.length }}
              </span>
            </p>

            <button
                v-for="conversation in groupConversations"
                :key="conversation.id"
                type="button"
                @click="selectConversation(conversation.id)"
                class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition"
                :class="
                selectedId === conversation.id
                  ? 'bg-indigo-50'
                  : 'hover:bg-slate-50'
              "
            >

              <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                  :style="avatarStyle(conversation.name)"
              >
                {{ getInitials(conversation.name) }}
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-baseline justify-between gap-1">
                  <span
                      class="truncate text-sm font-semibold"
                      :class="selectedId === conversation.id ? 'text-indigo-700' : 'text-slate-800'"
                  >
                    {{ conversation.name }}
                  </span>
                  <span class="shrink-0 text-xs text-slate-400">
                    {{ formatDate(lastMessage(conversation)?.date) }}
                  </span>
                </div>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                  {{ lastMessage(conversation)?.text || 'Aucun message' }}
                </p>
              </div>

            </button>

          </template>


          <!-- Section : Canaux -->
          <template v-if="channelConversations.length > 0">

            <p class="mb-1 mt-4 px-4 text-xs font-semibold uppercase tracking-widest text-slate-400">
              Canaux
              <span class="ml-1 font-normal text-slate-300">
                {{ channelConversations.length }}
              </span>
            </p>

            <button
                v-for="conversation in channelConversations"
                :key="conversation.id"
                type="button"
                @click="selectConversation(conversation.id)"
                class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition"
                :class="
                selectedId === conversation.id
                  ? 'bg-indigo-50'
                  : 'hover:bg-slate-50'
              "
            >

              <!-- Icône # pour les canaux -->
              <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-base font-bold text-white"
                  :style="avatarStyle(conversation.name)"
              >
                #
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-baseline justify-between gap-1">
                  <span
                      class="truncate text-sm font-semibold"
                      :class="selectedId === conversation.id ? 'text-indigo-700' : 'text-slate-800'"
                  >
                    {{ conversation.name }}
                  </span>
                  <span class="shrink-0 text-xs text-slate-400">
                    {{ formatDate(lastMessage(conversation)?.date) }}
                  </span>
                </div>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                  {{ lastMessage(conversation)?.text || 'Aucun message' }}
                </p>
              </div>

            </button>

          </template>


          <!-- Aucune conversation trouvée -->
          <p
              v-if="filteredConversations.length === 0"
              class="px-4 py-10 text-center text-sm text-slate-400"
          >
            Aucune conversation trouvée.
          </p>

        </div>

      </aside>


      <!-- ================================================
           16. ZONE DES MESSAGES
           ================================================ -->

      <!--
        Sur mobile : visible uniquement quand une conversation
        est ouverte (selectedId !== null).
        Sur desktop (md+) : toujours visible via md:flex.
      -->
      <section
          class="min-h-0 flex-col bg-slate-50 md:col-span-2"
          :class="selectedId !== null ? 'flex' : 'hidden md:flex'"
      >


        <!-- Conversation sélectionnée -->
        <template v-if="selectedConversation">


          <!-- ============================================
               17. EN-TÊTE DE LA CONVERSATION OUVERTE
               ============================================ -->

          <div class="flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-3 py-3 shadow-sm md:px-6">

            <!-- Bouton retour vers la liste (mobile uniquement) -->
            <button
                type="button"
                @click="selectedId = null"
                class="shrink-0 rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 md:hidden"
                aria-label="Retour à la liste"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
              </svg>
            </button>

            <!-- Avatar -->
            <div class="relative">
              <div
                  class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold text-white"
                  :style="avatarStyle(selectedConversation.name)"
              >
                {{ getInitials(selectedConversation.name) }}
              </div>
              <!-- Présence en ligne non implémentée. -->
            </div>

            <!-- Nom et informations -->
            <div class="flex-1 overflow-hidden">
              <h2 class="truncate text-sm font-semibold text-slate-800">
                {{ selectedConversation.name }}
              </h2>
              <p class="truncate text-xs text-slate-500">
                {{ conversationTypeLabel(selectedConversation.type) }}
                <template v-if="selectedConversation.type === 'group'">
                  · {{ (selectedConversation.members ?? []).length }} membres :
                  {{ (selectedConversation.members ?? []).join(', ') }}
                </template>
              </p>
            </div>

            <!-- Badge type -->
            <span
                class="hidden shrink-0 rounded-full px-3 py-1 text-xs font-medium sm:inline-flex"
                :class="{
                'bg-indigo-50 text-indigo-700': selectedConversation.type === 'direct',
                'bg-sky-50 text-sky-700': selectedConversation.type === 'group',
                'bg-amber-50 text-amber-700': selectedConversation.type === 'channel',
              }"
            >
              {{ conversationTypeLabel(selectedConversation.type) }}
            </span>

          </div>


          <!-- ============================================
               18. HISTORIQUE DES MESSAGES
               ============================================ -->

          <div class="flex-1 space-y-4 overflow-y-auto px-3 py-4 md:px-6 md:py-5">

            <div
                v-for="message in selectedConversation.messages"
                :key="message.id"
                class="flex items-end gap-2"
                :class="message.mine ? 'flex-row-reverse' : 'flex-row'"
            >

              <!-- Avatar de l'expéditeur (uniquement pour les autres) -->
              <div
                  v-if="!message.mine"
                  class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                  :style="avatarStyle(message.author)"
              >
                {{ getInitials(message.author) }}
              </div>

              <!-- Bulle du message -->
              <div
                  class="max-w-[80%] rounded-2xl px-4 py-2.5 shadow-sm md:max-w-[65%]"
                  :class="
                  message.mine
                    ? 'rounded-br-sm bg-indigo-600 text-white'
                    : 'rounded-bl-sm bg-white text-slate-800'
                "
              >

                <!--
                  Auteur affiché uniquement dans les groupes,
                  pour les messages des autres participants.
                -->
                <p
                    v-if="!message.mine && selectedConversation.type === 'group'"
                    class="mb-1 text-xs font-semibold text-indigo-500"
                >
                  {{ message.author }}
                </p>

                <!-- Contenu du message -->
                <p class="whitespace-pre-wrap break-words text-sm leading-relaxed">
                  {{ message.text }}
                </p>

                <!-- Horodatage -->
                <p
                    class="mt-1 text-right text-xs"
                    :class="message.mine ? 'text-indigo-200' : 'text-slate-400'"
                >
                  {{ formatMessageDate(message.date) }}
                </p>

              </div>

            </div>


            <!-- Conversation sans message -->
            <div
                v-if="selectedConversation.messages.length === 0"
                class="flex flex-col items-center justify-center py-16 text-slate-400"
            >
              <svg
                  class="mb-3 h-10 w-10 text-slate-300"
                  fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2
.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
              </svg>
              <p class="text-sm">Aucun message pour le moment.</p>
              <p class="mt-1 text-xs">Soyez le premier à écrire !</p>
            </div>

          </div>


          <!-- ============================================
               19. FORMULAIRE D'ENVOI
               ============================================ -->

          <!-- Formulaire d'envoi des messages -->
          <form
              @submit.prevent="sendMessage"
              class="shrink-0 border-t border-slate-200 bg-white px-4 py-3"
          >

            <!--
              Zone de saisie intégrée avec le bouton d'envoi.
              @keydown.enter.prevent : envoie le message avec
              la touche Entrée sans créer une nouvelle ligne.
            -->
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 transition focus-within:border-indigo-300 focus-within:bg-white focus-within:ring-2 focus-within:ring-indigo-100">

              <input
                  v-model="newMessage"
                  type="text"
                  maxlength="4000"
                  placeholder="Écrire un message..."
                  class="min-w-0 flex-1 bg-transparent text-sm text-slate-800 placeholder-slate-400 focus:outline-none"
              />

              <!-- Bouton d'envoi avec icône -->
              <button
                  type="submit"
                  :disabled="!newMessage.trim() || !selectedConversation"
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40"
              >
                <svg class="h-4 w-4 rotate-[15deg]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
                </svg>
              </button>

            </div>

            <!-- Compteur de caractères du message -->
            <p class="mt-1.5 text-right text-xs text-slate-400">
              {{ newMessage.length }}/4 000 · Entrée pour envoyer
            </p>

          </form>

        </template>


        <!-- ============================================
             20. AUCUNE CONVERSATION SÉLECTIONNÉE
             ============================================ -->

        <div
            v-else
            class="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-slate-400"
        >
          <svg
              class="h-14 w-14 text-slate-200"
              fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 4
8.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
          </svg>
          <div class="text-center">
            <p class="text-sm font-medium text-slate-500">Aucune conversation sélectionnée</p>
            <p class="mt-1 text-xs text-slate-400">
              Choisissez une conversation dans la liste pour afficher les messages.
            </p>
          </div>
        </div>

      </section>

    </main>

  </div>

</template>
