<?php

declare(strict_types=1);

namespace App\Enums\ApplicationParameter;

enum SpendlyApplicationParameterEnum: string
{
    case WalletLimitFree = 'wallet_limit_free';
    case GoalLimitFree = 'goal_limit_free';
    case RecurringLimitFree = 'recurring_limit_free';
    case StatsMonthsFree = 'stats_months_free';
    case StatsMonthsPro = 'stats_months_pro';
    case TransactionHistoryDaysFree = 'transaction_history_days_free';
    case ProPrice = 'pro_price';
    case ProTrialDays = 'pro_trial_days';
    case RegistrationEnabled = 'registration_enabled';

    public function getLabel(): string
    {
        return match ($this) {
            self::WalletLimitFree => 'Limite de portefeuilles (Free)',
            self::GoalLimitFree => "Limite d'objectifs (Free)",
            self::RecurringLimitFree => 'Limite de transactions automatiques (Free)',
            self::StatsMonthsFree => 'Mois de statistiques (Free)',
            self::StatsMonthsPro => 'Mois de statistiques (Pro)',
            self::TransactionHistoryDaysFree => 'Historique des transactions en jours (Free)',
            self::ProPrice => 'Prix du plan Pro',
            self::ProTrialDays => 'Durée du trial Pro (jours)',
            self::RegistrationEnabled => 'Inscriptions ouvertes',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::WalletLimitFree => 'Nombre maximum de portefeuilles pour un compte Free',
            self::GoalLimitFree => "Nombre maximum d'objectifs d'épargne pour un compte Free",
            self::RecurringLimitFree => 'Nombre maximum de transactions automatiques pour un compte Free',
            self::StatsMonthsFree => 'Nombre de mois affichés dans les statistiques pour un compte Free',
            self::StatsMonthsPro => 'Nombre de mois affichés dans les statistiques pour un compte Pro',
            self::TransactionHistoryDaysFree => "Nombre de jours d'historique des transactions accessibles en Free",
            self::ProPrice => 'Prix mensuel du plan Pro (en euros)',
            self::ProTrialDays => 'Nombre de jours de trial Pro accordés lors d\'un essai',
            self::RegistrationEnabled => 'Ouvrir ou fermer les inscriptions (0 = fermées, 1 = ouvertes)',
        };
    }

    public function getDefaultValue(): string
    {
        return match ($this) {
            self::WalletLimitFree => '1',
            self::GoalLimitFree => '2',
            self::RecurringLimitFree => '5',
            self::StatsMonthsFree => '1',
            self::StatsMonthsPro => '6',
            self::TransactionHistoryDaysFree => '90',
            self::ProPrice => '9.99',
            self::ProTrialDays => '30',
            self::RegistrationEnabled => '1',
        };
    }

    public function getIntValue(): int
    {
        return (int) $this->getDefaultValue();
    }

    public function getBoolValue(): bool
    {
        return $this->getDefaultValue() === '1';
    }

    public function getFloatValue(): float
    {
        return (float) $this->getDefaultValue();
    }
}
