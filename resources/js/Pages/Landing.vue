
<script setup>

/**
 * ============================================================
 * MESSENGERCUSTOM — PAGE D'ACCUEIL (LANDING PAGE)
 * ============================================================
 *
 * Technologies utilisées :
 * - Vue.js 3 : affichage dynamique des données
 * - Inertia.js : navigation entre Laravel et Vue
 * - Tailwind CSS : mise en page et design responsive
 *
 * Ce composant contient :
 * 1. Une barre de navigation
 * 2. Une présentation commerciale (Hero Section)
 * 3. Un aperçu de la messagerie
 * 4. Une présentation des fonctionnalités
 * 5. Deux formules tarifaires : Free et Pro
 * 6. Un appel à l'action final
 * 7. Un pied de page
 *
 * <script setup> utilise la Composition API de Vue 3.
 * Les variables déclarées ici sont directement
 * accessibles dans le <template>.
 */

// ============================================================
// 1. IMPORTATION DES COMPOSANTS INERTIA
// ============================================================

import { Head, Link } from '@inertiajs/vue3'

/**
 * Head :
 * Permet de définir le titre HTML de la page.
 *
 * Link :
 * Permet de naviguer vers les routes Laravel
 * en utilisant le système de navigation Inertia.
 *
 * Contrairement à un lien HTML classique,
 * Link permet généralement de changer de page
 * sans rechargement complet du navigateur.
 */


// ============================================================
// 2. TABLEAU DES FONCTIONNALITÉS
// ============================================================

/**
 * const :
 * Déclare une variable dont la référence
 * ne peut pas être réassignée.
 *
 * features :
 * Tableau JavaScript contenant trois objets.
 *
 * Chaque objet représente une fonctionnalité
 * de MessengerCustom.
 *
 * Propriétés :
 * - icon : pictogramme affiché
 * - title : titre de la fonctionnalité
 * - description : explication de la fonctionnalité
 *
 * Ce tableau sera parcouru dans le template
 * avec la directive Vue v-for.
 *
 * Remarque :
 * Il n'est pas nécessaire d'utiliser ref()
 * ici puisque ces données sont statiques.
 */
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


// ============================================================
// 3. TABLEAU DES FORMULES TARIFAIRES
// ============================================================

/**
 * plans :
 * Tableau contenant les différentes formules
 * commerciales de MessengerCustom.
 *
 * Chaque formule est représentée par un objet.
 *
 * Propriétés :
 *
 * name :
 * Nom de la formule.
 *
 * price :
 * Prix affiché sous forme de chaîne de caractères.
 *
 * description :
 * Texte de présentation de la formule.
 *
 * popular :
 * Valeur booléenne true ou false.
 *
 * Elle permet de mettre visuellement en avant
 * une formule dans le template.
 *
 * benefits :
 * Tableau de chaînes décrivant les avantages.
 *
 * IMPORTANT :
 * Ces données servent actuellement à l'affichage.
 * Elles n'implémentent pas automatiquement
 * la facturation ni les limites d'utilisation.
 */
const plans = [
  {
    name: 'Free',
    price: '0 €',
    description: 'Pour découvrir MessengerCustom.',

    // Cette formule n'est pas mise en avant.
    popular: false,

    // Liste des avantages de la formule Free.
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

    // La formule Pro est visuellement mise en avant.
    popular: true,

    // Liste des avantages de la formule Pro.
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

  <!-- ======================================================
       4. TITRE HTML DE LA PAGE
       ====================================================== -->

  <!--
    Head définit le titre affiché dans l'onglet
    du navigateur.

    Inertia gère ce titre sans nécessiter
    de manipulation manuelle du DOM.
  -->
  <Head title="MessengerCustom — Messagerie collaborative" />


  <!-- ======================================================
       5. CONTENEUR GLOBAL
       ====================================================== -->

  <!--
    min-h-screen :
    Hauteur minimale égale à celle de l'écran.

    bg-slate-50 :
    Arrière-plan gris très clair.

    text-slate-900 :
    Couleur de texte gris très foncé.

    Toutes les sections de la page sont
    contenues dans cet élément.
  -->
  <div class="min-h-screen bg-slate-50 text-slate-900">


    <!-- ====================================================
         6. BARRE DE NAVIGATION
         ==================================================== -->

    <!--
      header :
      Élément HTML sémantique représentant
      l'en-tête du site.

      border-b :
      Ajoute une bordure inférieure.

      bg-white :
      Définit un fond blanc.
    -->
    <header class="border-b border-slate-200 bg-white">

      <!--
        nav :
        Élément sémantique de navigation.

        mx-auto :
        Centre horizontalement le conteneur.

        flex :
        Active Flexbox.

        max-w-7xl :
        Limite la largeur maximale à 80rem.

        items-center :
        Aligne verticalement les éléments.

        justify-between :
        Place le logo à gauche et les liens à droite.

        px-6 :
        Padding horizontal de 1.5rem.

        py-5 :
        Padding vertical de 1.25rem.
      -->
      <nav
          class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5"
      >

        <!-- Logo textuel de MessengerCustom -->
        <Link
            href="/"
            class="text-xl font-bold text-blue-700"
        >
          MessengerCustom
        </Link>


        <!--
          Conteneur des liens de navigation.

          flex :
          Affiche les liens sur une même ligne.

          gap-5 :
          Ajoute un espace de 1.25rem entre les liens.
        -->
        <div class="flex items-center gap-5">

          <!-- Navigation Inertia vers l'accueil -->
          <Link href="/" class="hover:text-blue-600">
            Accueil
          </Link>

          <!--
            Lien HTML interne à la page.

            #fonctionnalites désigne l'élément
            possédant id="fonctionnalites".
          -->
          <a href="#fonctionnalites" class="hover:text-blue-600">
            Fonctionnalités
          </a>

          <!-- Défilement vers la section des tarifs -->
          <a href="#tarifs" class="hover:text-blue-600">
            Tarifs
          </a>

          <!-- Accès au formulaire de connexion -->
          <Link href="/login" class="hover:text-blue-600">
            Connexion
          </Link>

          <!--
            Bouton d'appel à l'action.

            rounded-lg :
            Coins arrondis.

            bg-blue-600 :
            Arrière-plan bleu.

            px-5 py-3 :
            Espacements intérieurs.

            hover:bg-blue-700 :
            Fond plus foncé au survol.
          -->
          <Link
              href="/login"
              class="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
          >
            Essayer maintenant
          </Link>

        </div>

      </nav>
    </header>


    <!-- ====================================================
         7. CONTENU PRINCIPAL
         ==================================================== -->

    <!--
      main :
      Contient le contenu principal de la page.

      Cet élément HTML améliore la structure
      sémantique et l'accessibilité du document.
    -->
    <main>


      <!-- ==================================================
           8. SECTION DE PRÉSENTATION (HERO)
           ================================================== -->

      <!--
        Une Hero Section est la première grande section
        visible sur une page d'accueil commerciale.

        Elle présente généralement :
        - le produit ;
        - sa proposition de valeur ;
        - un ou plusieurs boutons d'action.

        bg-slate-950 :
        Fond presque noir.

        px-6 :
        Padding horizontal.

        py-24 :
        Padding vertical de 6rem.

        text-white :
        Texte blanc.
      -->
      <section class="bg-slate-950 px-6 py-24 text-white">

        <!--
          max-w-5xl :
          Limite la largeur du contenu.

          mx-auto :
          Centre le conteneur.

          text-center :
          Centre horizontalement le texte.
        -->
        <div class="mx-auto max-w-5xl text-center">

          <!-- Sous-titre commercial -->
          <p class="mb-5 font-semibold tracking-widest text-blue-400">
            MESSAGERIE COLLABORATIVE B2B
          </p>


          <!--
            Titre principal de la page.

            text-4xl :
            Taille du texte par défaut.

            md:text-6xl :
            Augmente la taille du texte
            à partir du breakpoint md (768px par défaut).

            font-extrabold :
            Police très épaisse.

            leading-tight :
            Réduit l'espacement entre les lignes.
          -->
          <h1 class="text-4xl font-extrabold leading-tight md:text-6xl">

            Communiquez mieux.

            <!--
              span :
              Permet de mettre en forme une partie
              du titre sans créer un nouveau bloc.
            -->
            <span class="text-blue-400">
              Collaborez plus vite.
            </span>

          </h1>


          <!-- Description principale du produit -->
          <p class="mx-auto mt-8 max-w-2xl text-lg text-slate-300">
            MessengerCustom rassemble les conversations de votre équipe
            dans une interface simple et professionnelle.
          </p>


          <!-- ==============================================
               8.1. BOUTONS D'ACTION
               ============================================== -->

          <!--
            flex :
            Active Flexbox.

            flex-wrap :
            Autorise les boutons à passer à la ligne
            lorsque la largeur disponible est insuffisante.

            justify-center :
            Centre les boutons.

            gap-4 :
            Espacement entre les boutons.
          -->
          <div class="mt-10 flex flex-wrap justify-center gap-4">

            <!-- Bouton principal vers la connexion -->
            <Link
                href="/login"
                class="rounded-xl bg-blue-600 px-8 py-4 font-semibold text-white hover:bg-blue-700"
            >
              Essayer maintenant →
            </Link>


            <!-- Lien vers la section des tarifs -->
            <a
                href="#tarifs"
                class="rounded-xl border border-slate-500 px-8 py-4 font-semibold hover:bg-slate-800"
            >
              Voir les tarifs
            </a>

          </div>


          <!-- ==============================================
               8.2. APERÇU VISUEL DE LA MESSAGERIE
               ============================================== -->

          <!--
            Cet aperçu est une maquette statique.

            Les conversations et les messages affichés
            ici sont écrits directement dans le HTML.

            Ils ne proviennent pas de PostgreSQL
            et ne représentent pas une messagerie active.

            rounded-2xl :
            Coins fortement arrondis.

            shadow-2xl :
            Ombre importante.
          -->
          <div
              class="mx-auto mt-16 max-w-4xl rounded-2xl border border-slate-700 bg-slate-900 p-4 shadow-2xl"
          >

            <!--
              grid :
              Active CSS Grid.

              grid-cols-3 :
              Crée une grille de trois colonnes.

              gap-3 :
              Espacement entre les cellules.

              min-h-64 :
              Hauteur minimale de 16rem.

              text-left :
              Texte aligné à gauche.
            -->
            <div class="grid min-h-64 grid-cols-3 gap-3 text-left">


              <!-- ==========================================
                   8.2.1. COLONNE DES CONVERSATIONS
                   ========================================== -->

              <!--
                aside :
                Élément sémantique pour un contenu
                complémentaire au contenu principal.

                Cette colonne occupe une colonne
                sur les trois colonnes de la grille.
              -->
              <aside class="rounded-xl bg-slate-800 p-4">

                <p class="mb-5 text-sm font-bold text-blue-300">
                  Conversations
                </p>

                <!-- Conversation de groupe fictive -->
                <p class="mb-4 rounded-lg bg-slate-700 p-3 text-sm">
                  👥 Équipe projet
                </p>

                <!-- Conversation privée fictive -->
                <p class="rounded-lg p-3 text-sm text-slate-300">
                  💬 Discussion privée
                </p>

              </aside>


              <!-- ==========================================
                   8.2.2. ZONE DES MESSAGES
                   ========================================== -->

              <!--
                col-span-2 :
                Occupe deux colonnes de la grille.

                Avec la colonne aside, cela représente
                les trois colonnes disponibles.
              -->
              <div class="col-span-2 rounded-xl bg-slate-800 p-4">

                <!-- Nom de la conversation -->
                <p class="border-b border-slate-600 pb-3 font-semibold">
                  Équipe projet
                </p>


                <!--
                  justify-start :
                  Place le message à gauche.

                  Ce style représente visuellement
                  un message reçu.
                -->
                <div class="mt-6 flex justify-start">

                  <span class="rounded-xl bg-slate-700 px-4 py-3 text-sm">
                    Bonjour, où en est le projet ?
                  </span>

                </div>


                <!--
                  justify-end :
                  Place le message à droite.

                  Ce style représente visuellement
                  un message envoyé par l'utilisateur.
                -->
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


      <!-- ==================================================
           9. SECTION DES FONCTIONNALITÉS
           ================================================== -->

      <!--
        id="fonctionnalites" :
        Permet au lien #fonctionnalites
        de faire défiler la page jusqu'ici.
      -->
      <section id="fonctionnalites" class="px-6 py-24">

        <div class="mx-auto max-w-7xl">

          <h2 class="text-center text-3xl font-bold md:text-4xl">
            Tout pour simplifier vos échanges
          </h2>

          <p class="mt-4 text-center text-slate-600">
            Une plateforme pensée pour la communication professionnelle.
          </p>


          <!-- ==============================================
               9.1. GRILLE DES FONCTIONNALITÉS
               ============================================== -->

          <!--
            grid :
            Active CSS Grid.

            gap-8 :
            Espace de 2rem entre les cartes.

            md:grid-cols-3 :
            À partir de 768px, affiche trois colonnes.

            En dessous de 768px, la grille utilise
            une seule colonne par défaut.
          -->
          <div class="mt-14 grid gap-8 md:grid-cols-3">


            <!--
              v-for="feature in features" :

              Directive Vue.js qui parcourt
              le tableau JavaScript features.

              Une carte article est créée
              pour chaque objet du tableau.

              :key="feature.title" :

              Fournit une clé unique à Vue pour
              identifier chaque carte dans la liste.

              La clé doit être unique et stable.
            -->
            <article
                v-for="feature in features"
                :key="feature.title"
                class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
            >

              <!--
                Interpolation Vue :
                {{ feature.icon }}

                Affiche l'emoji de l'objet courant.
              -->
              <div class="mb-5 text-4xl">
                {{ feature.icon }}
              </div>


              <!-- Titre de la fonctionnalité -->
              <h3 class="mb-3 text-xl font-bold">
                {{ feature.title }}
              </h3>


              <!-- Description de la fonctionnalité -->
              <p class="leading-relaxed text-slate-600">
                {{ feature.description }}
              </p>

            </article>

          </div>
        </div>
      </section>


      <!-- ==================================================
           10. SECTION TARIFICATION
           ================================================== -->

      <!--
        id="tarifs" :
        Destination des liens href="#tarifs".

        bg-slate-100 :
        Fond gris clair pour distinguer cette section.
      -->
      <section id="tarifs" class="bg-slate-100 px-6 py-24">

        <div class="mx-auto max-w-5xl">

          <h2 class="text-center text-3xl font-bold md:text-4xl">
            Des tarifs simples et transparents
          </h2>

          <p class="mt-4 text-center text-slate-600">
            Commencez gratuitement et évoluez selon vos besoins.
          </p>


          <!-- ==============================================
               10.1. GRILLE DES FORMULES
               ============================================== -->

          <!--
            md:grid-cols-2 :
            Affiche deux colonnes sur les écrans
            de largeur moyenne ou supérieure.

            Sur mobile, les formules sont empilées.
          -->
          <div class="mt-14 grid gap-8 md:grid-cols-2">


            <!--
              v-for="plan in plans" :
              Parcourt les deux formules tarifaires.

              :key="plan.name" :
              Utilise le nom de la formule
              comme identifiant unique.

              flex flex-col :
              Organise les éléments de chaque carte
              verticalement.

              relative :
              Sert de référence au positionnement
              absolu du badge de la formule Pro.

              :class :
              Ajoute dynamiquement des classes Tailwind.

              Opérateur ternaire :

              condition ? valeurSiVrai : valeurSiFaux

              Si plan.popular est true :
              bordure bleue de 2 pixels.

              Sinon :
              bordure grise standard.
            -->
            <article
                v-for="plan in plans"
                :key="plan.name"
                class="relative flex flex-col rounded-2xl border bg-white p-8 shadow-sm"
                :class="plan.popular ? 'border-2 border-blue-600' : 'border-slate-200'"
            >


              <!-- ==========================================
                   10.2. BADGE DE LA FORMULE PRO
                   ========================================== -->

              <!--
                v-if="plan.popular" :
                Affiche le badge uniquement
                pour la formule mise en avant.

                absolute :
                Positionnement absolu.

                -top-4 :
                Décale le badge au-dessus de la carte.

                right-6 :
                Positionne le badge à droite.
              -->
              <span
                  v-if="plan.popular"
                  class="absolute -top-4 right-6 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
              >
                Pour les équipes
              </span>


              <!-- ==========================================
                   10.3. NOM ET DESCRIPTION
                   ========================================== -->

              <!-- Affiche Free ou Pro -->
              <h3 class="text-2xl font-bold">
                {{ plan.name }}
              </h3>

              <!-- Affiche la description correspondante -->
              <p class="mt-3 text-slate-600">
                {{ plan.description }}
              </p>


              <!-- ==========================================
                   10.4. PRIX ET PÉRIODICITÉ
                   ========================================== -->

              <div class="mt-8">

                <!--
                  Affiche le prix contenu dans plan.price.

                  text-5xl :
                  Texte de grande taille.

                  font-extrabold :
                  Texte très gras.
                -->
                <span class="text-5xl font-extrabold">
                  {{ plan.price }}
                </span>


                <!--
                  Opérateur ternaire :

                  Si plan.popular est true :
                  "/ personne / mois"

                  Sinon :
                  "/ mois"

                  Le tarif Pro est donc présenté
                  comme un prix mensuel par personne.
                -->
                <span class="text-slate-500">
                  {{ plan.popular ? '/ personne / mois' : '/ mois' }}
                </span>

              </div>


              <!-- ==========================================
                   10.5. LISTE DES AVANTAGES
                   ========================================== -->

              <!--
                ul :
                Liste HTML non ordonnée.

                flex-1 :
                Permet à cette zone de prendre
                l'espace vertical disponible.

                space-y-5 :
                Ajoute un espacement entre les lignes.

                Cela aide à aligner les boutons
                des deux cartes tarifaires.
              -->
              <ul class="mt-10 flex-1 space-y-5">


                <!--
                  v-for="benefit in plan.benefits" :
                  Parcourt les avantages de la formule.

                  :key="benefit" :
                  Utilise le texte de chaque avantage
                  comme clé unique dans cette liste.

                  items-start :
                  Aligne les éléments en haut.

                  gap-3 :
                  Espace entre la coche et le texte.
                -->
                <li
                    v-for="benefit in plan.benefits"
                    :key="benefit"
                    class="flex items-start gap-3"
                >

                  <!-- Symbole de validation -->
                  <span class="font-bold text-green-600">
                    ✓
                  </span>

                  <!-- Texte de l'avantage -->
                  <span>
                    {{ benefit }}
                  </span>

                </li>

              </ul>


              <!-- ==========================================
                   10.6. BOUTON DE SÉLECTION
                   ========================================== -->

              <!--
                Les deux boutons redirigent actuellement
                vers la page de connexion.

                Ils ne déclenchent pas un paiement
                et ne sélectionnent pas réellement
                une formule dans la base de données.

                :class :
                Applique un style différent selon
                la valeur de plan.popular.

                Pour Pro :
                bouton bleu.

                Pour Free :
                bouton gris très foncé.
              -->
              <Link
                  href="/login"
                  class="mt-10 block rounded-xl px-6 py-4 text-center font-semibold"
                  :class="
                  plan.popular
                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                    : 'bg-slate-900 text-white hover:bg-slate-800'
                "
              >

                <!--
                  Si la formule est mise en avant :
                  "Choisir Pro"

                  Sinon :
                  "Commencer gratuitement"
                -->
                {{ plan.popular ? 'Choisir Pro' : 'Commencer gratuitement' }}

              </Link>

            </article>

          </div>


          <!--
            Mention importante :

            Les quotas sont indicatifs.

            Leur affichage ne garantit pas que
            les restrictions soient implémentées
            dans le backend Laravel.
          -->
          <p class="mt-8 text-center text-sm text-slate-500">
            Quotas présentés à titre indicatif, sous réserve de leur implémentation.
          </p>

        </div>
      </section>


      <!-- ==================================================
           11. DERNIER APPEL À L'ACTION
           ================================================== -->

      <!--
        Cette section encourage le visiteur
        à découvrir ou essayer MessengerCustom.

        bg-blue-700 :
        Fond bleu foncé.

        text-center :
        Texte centré.

        text-white :
        Texte blanc.
      -->
      <section class="bg-blue-700 px-6 py-20 text-center text-white">

        <h2 class="text-3xl font-bold">
          Prêt à simplifier la communication de votre équipe ?
        </h2>

        <p class="mt-5 text-blue-100">
          Découvrez MessengerCustom dès aujourd'hui.
        </p>


        <!--
          inline-block :
          Permet d'appliquer des dimensions
          et des espacements à un lien inline.

          rounded-xl :
          Coins arrondis.

          bg-white :
          Fond blanc.

          hover:bg-blue-50 :
          Fond bleu très clair au survol.
        -->
        <Link
            href="/login"
            class="mt-8 inline-block rounded-xl bg-white px-8 py-4 font-bold text-blue-700 hover:bg-blue-50"
        >
          Essayer maintenant →
        </Link>

      </section>

    </main>


    <!-- ====================================================
         12. PIED DE PAGE
         ==================================================== -->

    <!--
      footer :
      Élément HTML sémantique représentant
      le pied de page du site.

      bg-slate-950 :
      Fond presque noir.

      text-slate-400 :
      Texte gris clair.
    -->
    <footer class="bg-slate-950 px-6 py-8 text-center text-slate-400">

      <!--
        {{ new Date().getFullYear() }}

        Expression JavaScript exécutée dans le template.

        new Date() :
        Crée un objet Date.

        getFullYear() :
        Retourne l'année courante.

        Cela évite d'écrire une année fixe
        qu'il faudrait modifier manuellement.
      -->
      <p>
        © {{ new Date().getFullYear() }} MessengerCustom.
      </p>

    </footer>

  </div>

</template>
