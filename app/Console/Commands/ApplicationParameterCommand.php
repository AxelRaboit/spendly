<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ApplicationParameter\SpendlyApplicationParameterEnum;
use App\Models\ApplicationParameter;
use Illuminate\Console\Command;

class ApplicationParameterCommand extends Command
{
    protected $signature = 'spendly:application-parameter {--dry-run : Affiche les changements sans les appliquer}';

    protected $description = 'Synchronise les paramètres applicatifs (crée les manquants, supprime les obsolètes).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->line('<comment>Mode dry-run — aucun changement ne sera enregistré.</comment>');
        }

        $enumCases = SpendlyApplicationParameterEnum::cases();
        $enumKeys = array_map(fn (SpendlyApplicationParameterEnum $case): string => $case->value, $enumCases);

        $existing = ApplicationParameter::all()->keyBy('key')->toArray();

        $created = $this->createMissing($enumCases, $existing, $dryRun);
        $deleted = $this->deleteObsolete($enumKeys, $existing, $dryRun);

        $this->info(sprintf('%d créé(s), %d supprimé(s).', $created, $deleted));

        return self::SUCCESS;
    }

    /**
     * @param  SpendlyApplicationParameterEnum[]  $enumCases
     * @param  array<string, array<string, string|null>>  $existing
     */
    private function createMissing(array $enumCases, array $existing, bool $dryRun): int
    {
        $created = 0;

        foreach ($enumCases as $case) {
            if (isset($existing[$case->value])) {
                $this->syncDescription($case, $existing[$case->value], $dryRun);

                continue;
            }

            $this->line(sprintf('  <info>+</info> %s (défaut : %s)', $case->value, $case->getDefaultValue()));
            $created++;

            if (! $dryRun) {
                ApplicationParameter::create([
                    'key' => $case->value,
                    'value' => $case->getDefaultValue(),
                    'description' => $case->getDescription(),
                ]);
            }
        }

        return $created;
    }

    /**
     * @param  array<string, string|null>  $existing
     */
    private function syncDescription(SpendlyApplicationParameterEnum $case, array $existing, bool $dryRun): void
    {
        if ($existing['description'] === $case->getDescription()) {
            return;
        }

        $this->line(sprintf('  <comment>~</comment> %s (description mise à jour)', $case->value));

        if (! $dryRun) {
            ApplicationParameter::where('key', $case->value)->update(['description' => $case->getDescription()]);
        }
    }

    /**
     * @param  string[]  $enumKeys
     * @param  array<string, array<string, string|null>>  $existing
     */
    private function deleteObsolete(array $enumKeys, array $existing, bool $dryRun): int
    {
        $deleted = 0;

        foreach (array_keys($existing) as $key) {
            if (in_array($key, $enumKeys, true)) {
                continue;
            }

            $this->line(sprintf('  <fg=red>-</fg=red> %s (obsolète)', $key));
            $deleted++;

            if (! $dryRun) {
                ApplicationParameter::where('key', $key)->delete();
            }
        }

        return $deleted;
    }
}
