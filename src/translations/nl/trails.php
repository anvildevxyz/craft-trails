<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'Activiteitenlogboeken',
    'Export' => 'Exporteren',
    'Settings' => 'Instellingen',

    // Permissions
    'View audit logs' => 'Auditlogboeken bekijken',
    'Export audit logs' => 'Auditlogboeken exporteren',
    'Manage settings' => 'Instellingen beheren',

    // Logs
    'Log Details' => 'Logboekdetails',
    'Back to Logs' => 'Terug naar logboeken',
    'No audit logs found.' => 'Geen auditlogboeken gevonden.',
    'Showing {count} of {total} logs' => '{count} van {total} logboeken weergegeven',
    'View' => 'Bekijken',
    'Filter' => 'Filteren',
    'Reset' => 'Herstellen',

    // Summary
    'Events (7d)' => 'Gebeurtenissen (7d)',
    'Logins' => 'Aanmeldingen',
    'Created' => 'Aangemaakt',
    'Updated' => 'Bijgewerkt',
    'Deleted' => 'Verwijderd',

    // Filters
    'Event Type' => 'Gebeurtenistype',
    'Category' => 'Categorie',
    'User' => 'Gebruiker',
    'Search' => 'Zoeken',
    'Search logs...' => 'Logboeken doorzoeken...',
    'All Events' => 'Alle gebeurtenissen',
    'All Categories' => 'Alle categorieën',
    'All Users' => 'Alle gebruikers',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'Tijdstempel',
    'Event' => 'Gebeurtenis',
    'User Information' => 'Gebruikersinformatie',
    'User ID' => 'Gebruikers-ID',
    'Username' => 'Gebruikersnaam',
    'Email' => 'E-mail',
    'IP Address' => 'IP-adres',
    'Session ID' => 'Sessie-ID',
    'Element Information' => 'Elementinformatie',
    'Element Type' => 'Elementtype',
    'Element ID' => 'Element-ID',
    'Element Title' => 'Elementtitel',
    'Site ID' => 'Site-ID',
    'Request Information' => 'Verzoeksinformatie',
    'Request Method' => 'Verzoekmethode',
    'Request URL' => 'Verzoek-URL',
    'User Agent' => 'Gebruikersagent',
    'Metadata' => 'Metadata',
    'Changes' => 'Wijzigingen',
    'Previous Value' => 'Vorige waarde',
    'New Value' => 'Nieuwe waarde',
    'Integrity' => 'Integriteit',
    'Hash' => 'Hash',

    // Pagination
    '← Previous' => '← Vorige',
    'Next →' => 'Volgende →',

    // Export
    'Export Logs' => 'Logboeken exporteren',
    'Export Audit Logs' => 'Auditlogboeken exporteren',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'Exporteer uw auditlogboeken voor compliancerapportage, back-up of analyse.',
    'From Date' => 'Vanaf datum',
    'To Date' => 'Tot datum',
    'Leave empty to include all history' => 'Leeg laten om de volledige geschiedenis op te nemen',
    'Leave empty to include up to now' => 'Leeg laten om tot en met nu op te nemen',
    'Export Format' => 'Exportindeling',
    'Choose the format for your export file' => 'Kies de indeling voor uw exportbestand',
    'Download Export' => 'Export downloaden',
    // Settings
    'Trails Settings' => 'Trails-instellingen',
    'General Settings' => 'Algemene instellingen',
    'Enable Logging' => 'Logboekregistratie inschakelen',
    'When disabled, no new audit events will be recorded.' => 'Wanneer uitgeschakeld, worden er geen nieuwe auditgebeurtenissen geregistreerd.',
    'Retention Period (Days)' => 'Bewaartermijn (dagen)',
    'How long to keep logs. Set to 0 to keep forever.' => 'Hoe lang logboeken bewaard worden. Stel in op 0 om ze voor altijd te bewaren.',
    'Current Storage' => 'Huidig opslaggebruik',
    'logs' => 'logboeken',
    'from' => 'vanaf',
    '{count} logs scheduled for cleanup' => '{count} logboeken gepland voor opschonen',
    'What to Log' => 'Wat te registreren',
    'Element Changes' => 'Elementwijzigingen',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'Registreer wanneer vermeldingen, assets, gebruikers en andere elementen worden aangemaakt, bijgewerkt of verwijderd.',
    'User Authentication' => 'Gebruikersauthenticatie',
    'Log user logins and logouts.' => 'Registreer aanmeldingen en afmeldingen van gebruikers.',
    'Failed Login Attempts' => 'Mislukte aanmeldpogingen',
    'Log failed authentication attempts for security monitoring.' => 'Registreer mislukte authenticatiepogingen voor beveiligingsbewaking.',
    'Config Changes' => 'Configuratiewijzigingen',
    'Log when project config changes are applied.' => 'Registreer wanneer projectconfiguratiewijzigingen worden toegepast.',
    'Asset Operations' => 'Assetbewerkingen',
    'Log asset uploads, deletions, and modifications.' => 'Registreer uploads, verwijderingen en wijzigingen van assets.',
    'Data Capture' => 'Gegevensvastlegging',
    'Capture IP Addresses' => 'IP-adressen vastleggen',
    'Store the IP address of each action.' => 'Sla het IP-adres van elke actie op.',
    'Anonymize IPs (GDPR)' => 'IP\'s anonimiseren (AVG)',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'Vervang het laatste octet van IP-adressen door 0 voor privacynaleving.',
    'Capture User Agent' => 'Gebruikersagent vastleggen',
    'Store the browser/device information.' => 'Sla de browser-/apparaatinformatie op.',
    'Capture Field Changes' => 'Veldwijzigingen vastleggen',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'Sla de waarden voor/na veldwijzigingen op. Waarschuwing: verhoogt het opslaggebruik.',
    'This can significantly increase database size.' => 'Dit kan de databasegrootte aanzienlijk vergroten.',
    'Email Alerts' => 'E-mailmeldingen',
    'Enable Alerts' => 'Meldingen inschakelen',
    'Send email notifications for suspicious activity.' => 'Stuur e-mailmeldingen voor verdachte activiteiten.',
    'Alert Email' => 'Meldingsadres',
    'Email address to receive security alerts.' => 'E-mailadres voor het ontvangen van beveiligingsmeldingen.',
    'Failed Login Threshold' => 'Drempel mislukte aanmeldingen',
    'Send alert after this many failed logins from the same IP within 1 hour.' => 'Stuur een melding na dit aantal mislukte aanmeldingen van hetzelfde IP-adres binnen 1 uur.',
    'Save Settings' => 'Instellingen opslaan',
    'Maintenance' => 'Onderhoud',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => 'Voer handmatig opschonen uit om oude logboeken te verwijderen op basis van uw bewaartermijn.',
    'Run Cleanup Now' => 'Nu opschonen',
    'Are you sure you want to delete old logs?' => 'Weet u zeker dat u de oude logboeken wilt verwijderen?',
    'Couldn\'t save settings.' => 'Instellingen konden niet worden opgeslagen.',
    'Settings saved.' => 'Instellingen opgeslagen.',
    'Deleted {count} old log entries.' => '{count} oude logboekvermeldingen verwijderd.',

    // New settings
    'Permission Changes' => 'Machtigingswijzigingen',
    'Log when user permissions or group assignments change.' => 'Registreer wanneer gebruikersmachtigingen of groepstoewijzingen worden gewijzigd.',
    'Log asset uploads, replacements, and modifications.' => 'Registreer uploads, vervangingen en wijzigingen van assets.',

    // Exclusions
    'Exclusions' => 'Uitsluitingen',
    'Specify element types and sections to exclude from logging.' => 'Geef de elementtypen en secties op die van logboekregistratie worden uitgesloten.',
    'Excluded Element Types' => 'Uitgesloten elementtypen',
    'Select element types to exclude from audit logging.' => 'Selecteer de elementtypen die van auditlogregistratie worden uitgesloten.',
    'Excluded Sections' => 'Uitgesloten secties',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => 'Selecteer de secties die van auditlogregistratie worden uitgesloten (alleen van toepassing op vermeldingselementen).',

    // External Log Shipping
    'External Log Shipping' => 'Extern logboek verzenden',
    'Send audit logs to external services for centralized monitoring.' => 'Stuur auditlogboeken naar externe diensten voor gecentraliseerde bewaking.',
    'Enable External Shipping' => 'Extern verzenden inschakelen',
    'Send logs to an external service via queue.' => 'Stuur logboeken via een wachtrij naar een externe dienst.',
    'Provider' => 'Provider',
    'Select the external logging provider.' => 'Selecteer de externe logboekprovider.',
    'Endpoint URL' => 'Endpoint-URL',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'De URL waarnaar logboeken worden gestuurd (bijv. Splunk HEC-endpoint, Datadog intake of aangepaste webhook).',
    'API Key' => 'API-sleutel',
    'API key or token for authentication with the external service.' => 'API-sleutel of token voor authenticatie bij de externe dienst.',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI: php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => 'Auditlogboek verzenden naar {provider}',

    // v1.1 — Date filters
    'Date From' => 'Datum van',
    'Date To' => 'Datum tot',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => 'Opschonen automatisch uitvoeren volgens een dagelijks schema',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'GC-gebaseerd opschonen wordt sowieso automatisch uitgevoerd. Deze optie voegt een voorspelbaar dagelijks schema toe via de wachtrij.',
    'Scheduled audit log retention cleanup' => 'Gepland opschonen van auditlogboekbewaring',

    // v1.1 — Dashboard widget
    'Audit Activity' => 'Auditactiviteit',
    'Events' => 'Gebeurtenissen',
    'Insufficient permissions to view audit data.' => 'Onvoldoende rechten om auditgegevens te bekijken.',
    'Lookback Period' => 'Terugkijkperiode',
    '7 days' => '7 dagen',
    '14 days' => '14 dagen',
    '30 days' => '30 dagen',
    'View all logs →' => 'Alle logboeken bekijken →',

    // v1.2 — Pagination
    'Per page' => 'Per pagina',
    'Page {current} of {total}' => 'Pagina {current} van {total}',
    '« First' => '« Eerste',
    '‹ Prev' => '‹ Vorige',
    'Next ›' => 'Volgende ›',
    'Last »' => 'Laatste »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => '{count} auditlogboeken verzenden naar {provider}',

    // v1.3 — Integrity check
    'Integrity Check' => 'Integriteitscontrole',
    'Log Integrity Verification' => 'Verificatie van logboekintegriteit',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'Doorloopt elk auditlogboek en verifieert de HMAC-SHA256 integriteits-hash. Gemanipuleerde records worden hier en via de CLI gemeld.',
    'Last Run' => 'Laatste uitvoering',
    'Run At' => 'Uitgevoerd op',
    'Verified' => 'Geverifieerd',
    'Tampered' => 'Gemanipuleerd',
    'None' => 'Geen',
    'records' => 'records',
    'IDs:' => 'ID\'s:',
    'No verification runs yet.' => 'Nog geen verificaties uitgevoerd.',
    'Verify All Logs Now' => 'Alle logboeken nu verifiëren',
    'May take a few minutes for large log tables.' => 'Kan enkele minuten duren voor grote logboektabellen.',
    'Verified — record integrity intact' => 'Geverifieerd — recordintegriteit intact',
    'WARNING — record may have been tampered with' => 'WAARSCHUWING — record is mogelijk gemanipuleerd',
    'Not verified' => 'Niet geverifieerd',
    'Verification' => 'Verificatie',
    'Verification complete. {count} tampered records found.' => 'Verificatie voltooid. {count} gemanipuleerde records gevonden.',
    'All {total} logs verified OK.' => 'Alle {total} logboeken succesvol geverifieerd.',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Webhook-geheim',
    'Webhook Signature' => 'Webhook-handtekening',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => 'Onderteken uitgaande webhook-payloads met een gedeeld geheim zodat ontvangers de authenticiteit kunnen verifiëren.',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Optioneel gedeeld geheim voor het ondertekenen van webhook-payloads. Ondersteunt $ENV_VAR-syntaxis.',

    // v1.3 — Settings navigation & descriptions
    'General' => 'Algemeen',
    'Logging' => 'Logboekregistratie',
    'Alerts' => 'Meldingen',
    'External Shipping' => 'Extern verzenden',
    'Alert Settings' => 'Meldingsinstellingen',
    'Logging Settings' => 'Logboekinstellingen',
    'Configure basic audit trail settings.' => 'Configureer de basisinstellingen voor het auditlogboek.',
    'Configure which events should be tracked.' => 'Configureer welke gebeurtenissen gevolgd moeten worden.',
    'Configure email notifications for suspicious activity.' => 'Configureer e-mailmeldingen voor verdachte activiteiten.',
    'Configure what additional data to capture with each audit event.' => 'Configureer welke aanvullende gegevens bij elke auditgebeurtenis worden vastgelegd.',

    // v1.3 — Log view extras
    'Location' => 'Locatie',
    'Element' => 'Element',
    'No changes detected.' => 'Geen wijzigingen gedetecteerd.',
    'Field' => 'Veld',
    'Old' => 'Oud',
    'New' => 'Nieuw',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'IP-geolocatie',
    'Enable geolocation' => 'Geolocatie inschakelen',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => 'IP-adressen omzetten naar land/regio/stad via een externe API. Voegt een kleine vertraging toe per logboekschrijfactie.',
    'Geolocation endpoint' => 'Geolocatie-endpoint',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'URL-sjabloon. Standaard: http://ip-api.com/json/ (gratis, met snelheidslimiet).',

    // v1.3 — Export validation
    'Invalid "from" date format.' => 'Ongeldig datumformaat voor "van".',
    'Invalid "to" date format.' => 'Ongeldig datumformaat voor "tot".',
    '"From" date must be before "to" date.' => '"Van"-datum moet voor de "tot"-datum liggen.',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => 'De bewaartermijn is ingesteld om logboeken voor altijd te bewaren. Zonder een gepland opschoontaak zal de database onbeperkt groeien. Overweeg een bewaartermijn in te stellen of `php craft trails/retention/cleanup` via cron te plannen.',
];
