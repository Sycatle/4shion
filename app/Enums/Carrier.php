<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Carriers the shop hands parcels to. Tracking is a public link built from the tracking number:
 * no carrier API is called (label printing and relay-point lookup need carrier credentials).
 */
enum Carrier: string
{
    case Colissimo = 'colissimo';
    case MondialRelay = 'mondial_relay';
    case Chronopost = 'chronopost';
    case Dhl = 'dhl';
    case Ups = 'ups';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Colissimo => 'Colissimo',
            self::MondialRelay => 'Mondial Relay',
            self::Chronopost => 'Chronopost',
            self::Dhl => 'DHL',
            self::Ups => 'UPS',
            self::Other => 'Autre transporteur',
        };
    }

    public function trackingUrl(string $trackingNumber): ?string
    {
        $number = rawurlencode($trackingNumber);

        return match ($this) {
            self::Colissimo => "https://www.laposte.fr/outils/suivre-vos-envois?code={$number}",
            self::MondialRelay => "https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition={$number}",
            self::Chronopost => "https://www.chronopost.fr/tracking-no-cms/suivi-page?listeNumerosLT={$number}",
            self::Dhl => "https://www.dhl.com/fr-fr/home/tracking.html?tracking-id={$number}",
            self::Ups => "https://www.ups.com/track?tracknum={$number}",
            self::Other => null,
        };
    }
}
