<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Commande de rattrapage (backfill).
 *
 * CONTEXTE :
 * Avant le correctif de RegisterController, les utilisateurs inscrits en
 * production n'étaient rattachés à aucune organisation. Sans organisation
 * commune active, ConversationController refusait toute création de
 * conversation directe, de groupe ou d'ajout à un groupe.
 *
 * Cette commande rattrape les comptes DÉJÀ existants : elle garantit
 * l'existence de l'organisation par défaut (slug « default ») puis y
 * ajoute, comme membres actifs, tous les utilisateurs qui n'appartiennent
 * encore à aucune organisation.
 *
 * Idempotente : elle peut être relancée sans créer de doublon.
 *
 * Utilisation :
 *   php artisan orgs:backfill-default          (applique les changements)
 *   php artisan orgs:backfill-default --dry-run (affiche sans rien modifier)
 */
class BackfillDefaultOrganization extends Command
{
    /**
     * Signature de la commande.
     */
    protected $signature = 'orgs:backfill-default {--dry-run : Simule l\'opération sans écrire en base}';

    /**
     * Description affichée par « php artisan list ».
     */
    protected $description = 'Rattache les utilisateurs existants sans organisation à l\'organisation par défaut (membre actif).';

    /**
     * Exécution de la commande.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode simulation (--dry-run) : aucune écriture ne sera effectuée.');
        }

        // ==================================================
        // 1. GARANTIR L'EXISTENCE DE L'ORGANISATION PAR DÉFAUT
        // ==================================================

        // On réutilise le même slug « default » que RegisterController
        // afin que rattrapage et inscriptions pointent vers la même organisation.
        $orgId = DB::table('organizations')
            ->where('slug', 'default')
            ->value('id');

        if ($orgId === null) {
            $orgId = (string) Str::uuid();

            if (! $dryRun) {
                DB::table('organizations')->insert([
                    'id' => $orgId,
                    'name' => 'MessengerCustom',
                    'slug' => 'default',
                    'created_at' => now(),
                ]);
            }

            $this->info("Organisation par défaut créée (id = {$orgId}).");
        } else {
            $this->info("Organisation par défaut trouvée (id = {$orgId}).");
        }

        // ==================================================
        // 2. IDENTIFIER LES UTILISATEURS SANS AUCUNE ORGANISATION
        // ==================================================

        // On ne touche pas aux utilisateurs déjà membres d'une organisation
        // (quelle qu'elle soit) : seuls les comptes « orphelins » sont visés.
        $usersWithoutOrg = DB::table('users')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('org_members')
                    ->whereColumn('org_members.user_id', 'users.id');
            })
            ->pluck('users.id');

        $total = $usersWithoutOrg->count();

        if ($total === 0) {
            $this->info('Aucun utilisateur à rattraper : tous appartiennent déjà à une organisation.');

            return self::SUCCESS;
        }

        $this->info("{$total} utilisateur(s) sans organisation à rattacher.");

        // ==================================================
        // 3. RATTACHER CHAQUE UTILISATEUR COMME MEMBRE ACTIF
        // ==================================================

        if ($dryRun) {
            $this->line('Simulation terminée : aucun rattachement écrit.');

            return self::SUCCESS;
        }

        $now = now();
        $attached = 0;

        // Insertion par lots dans une transaction pour la cohérence.
        DB::transaction(function () use ($usersWithoutOrg, $orgId, $now, &$attached) {
            foreach ($usersWithoutOrg->chunk(500) as $chunk) {
                $rows = $chunk->map(fn ($userId) => [
                    'org_id' => $orgId,
                    'user_id' => $userId,
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => $now,
                ])->all();

                // insertOrIgnore : sécurité supplémentaire contre les doublons
                // sur la clé primaire composée (org_id, user_id).
                $attached += DB::table('org_members')->insertOrIgnore($rows);
            }
        });

        $this->info("{$attached} rattachement(s) effectué(s) avec succès.");

        return self::SUCCESS;
    }
}