<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'Journaux d\'activité',
    'Export' => 'Exporter',
    'Settings' => 'Paramètres',

    // Permissions
    'View audit logs' => 'Consulter les journaux d\'audit',
    'Export audit logs' => 'Exporter les journaux d\'audit',
    'Manage settings' => 'Gérer les paramètres',

    // Logs
    'Log Details' => 'Détails du journal',
    'Back to Logs' => 'Retour aux journaux',
    'No audit logs found.' => 'Aucun journal d\'audit trouvé.',
    'Showing {count} of {total} logs' => 'Affichage de {count} sur {total} journaux',
    'View' => 'Voir',
    'Filter' => 'Filtrer',
    'Reset' => 'Réinitialiser',

    // Summary
    'Events (7d)' => 'Événements (7j)',
    'Logins' => 'Connexions',
    'Created' => 'Créés',
    'Updated' => 'Mis à jour',
    'Deleted' => 'Supprimés',

    // Filters
    'Event Type' => 'Type d\'événement',
    'Category' => 'Catégorie',
    'User' => 'Utilisateur',
    'Search' => 'Rechercher',
    'Search logs...' => 'Rechercher dans les journaux...',
    'All Events' => 'Tous les événements',
    'All Categories' => 'Toutes les catégories',
    'All Users' => 'Tous les utilisateurs',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'Horodatage',
    'Event' => 'Événement',
    'User Information' => 'Informations utilisateur',
    'User ID' => 'ID utilisateur',
    'Username' => 'Nom d\'utilisateur',
    'Email' => 'E-mail',
    'IP Address' => 'Adresse IP',
    'Session ID' => 'ID de session',
    'Element Information' => 'Informations sur l\'élément',
    'Element Type' => 'Type d\'élément',
    'Element ID' => 'ID de l\'élément',
    'Element Title' => 'Titre de l\'élément',
    'Site ID' => 'ID du site',
    'Request Information' => 'Informations sur la requête',
    'Request Method' => 'Méthode de requête',
    'Request URL' => 'URL de la requête',
    'User Agent' => 'Agent utilisateur',
    'Metadata' => 'Métadonnées',
    'Changes' => 'Modifications',
    'Previous Value' => 'Valeur précédente',
    'New Value' => 'Nouvelle valeur',
    'Integrity' => 'Intégrité',
    'Hash' => 'Hash',

    // Pagination
    '← Previous' => '← Précédent',
    'Next →' => 'Suivant →',

    // Export
    'Export Logs' => 'Exporter les journaux',
    'Export Audit Logs' => 'Exporter les journaux d\'audit',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'Exportez vos journaux d\'audit pour les rapports de conformité, les sauvegardes ou les analyses.',
    'From Date' => 'Date de début',
    'To Date' => 'Date de fin',
    'Leave empty to include all history' => 'Laisser vide pour inclure tout l\'historique',
    'Leave empty to include up to now' => 'Laisser vide pour inclure jusqu\'à maintenant',
    'Export Format' => 'Format d\'exportation',
    'Choose the format for your export file' => 'Choisissez le format de votre fichier d\'exportation',
    'Download Export' => 'Télécharger l\'exportation',
    // Settings
    'Trails Settings' => 'Paramètres de Trails',
    'General Settings' => 'Paramètres généraux',
    'Enable Logging' => 'Activer la journalisation',
    'When disabled, no new audit events will be recorded.' => 'Lorsque désactivé, aucun nouvel événement d\'audit ne sera enregistré.',
    'Retention Period (Days)' => 'Durée de rétention (jours)',
    'How long to keep logs. Set to 0 to keep forever.' => 'Durée de conservation des journaux. Définir à 0 pour les conserver indéfiniment.',
    'Current Storage' => 'Stockage actuel',
    'logs' => 'journaux',
    'from' => 'depuis',
    '{count} logs scheduled for cleanup' => '{count} journaux programmés pour suppression',
    'What to Log' => 'Que journaliser',
    'Element Changes' => 'Modifications d\'éléments',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'Journaliser la création, la mise à jour ou la suppression d\'entrées, de ressources, d\'utilisateurs et d\'autres éléments.',
    'User Authentication' => 'Authentification des utilisateurs',
    'Log user logins and logouts.' => 'Journaliser les connexions et déconnexions des utilisateurs.',
    'Failed Login Attempts' => 'Tentatives de connexion échouées',
    'Log failed authentication attempts for security monitoring.' => 'Journaliser les tentatives d\'authentification échouées pour la surveillance de la sécurité.',
    'Config Changes' => 'Modifications de configuration',
    'Log when project config changes are applied.' => 'Journaliser lorsque des modifications de la configuration du projet sont appliquées.',
    'Asset Operations' => 'Opérations sur les ressources',
    'Log asset uploads, deletions, and modifications.' => 'Journaliser les téléversements, suppressions et modifications de ressources.',
    'Data Capture' => 'Collecte de données',
    'Capture IP Addresses' => 'Capturer les adresses IP',
    'Store the IP address of each action.' => 'Enregistrer l\'adresse IP de chaque action.',
    'Anonymize IPs (GDPR)' => 'Anonymiser les IP (RGPD)',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'Remplacer le dernier octet des adresses IP par 0 pour la conformité à la vie privée.',
    'Capture User Agent' => 'Capturer l\'agent utilisateur',
    'Store the browser/device information.' => 'Enregistrer les informations sur le navigateur/appareil.',
    'Capture Field Changes' => 'Capturer les modifications de champs',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'Enregistrer les valeurs avant/après les modifications de champs. Attention : augmente l\'utilisation du stockage.',
    'This can significantly increase database size.' => 'Cela peut augmenter considérablement la taille de la base de données.',
    'Email Alerts' => 'Alertes par e-mail',
    'Enable Alerts' => 'Activer les alertes',
    'Send email notifications for suspicious activity.' => 'Envoyer des notifications par e-mail pour les activités suspectes.',
    'Alert Email' => 'E-mail d\'alerte',
    'Email address to receive security alerts.' => 'Adresse e-mail pour recevoir les alertes de sécurité.',
    'Failed Login Threshold' => 'Seuil de tentatives échouées',
    'Send alert after this many failed logins from the same IP within 1 hour.' => 'Envoyer une alerte après ce nombre de connexions échouées depuis la même IP en 1 heure.',
    'Save Settings' => 'Enregistrer les paramètres',
    'Maintenance' => 'Maintenance',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => 'Exécuter manuellement le nettoyage de rétention pour supprimer les anciens journaux selon votre politique de rétention.',
    'Run Cleanup Now' => 'Lancer le nettoyage maintenant',
    'Are you sure you want to delete old logs?' => 'Êtes-vous sûr de vouloir supprimer les anciens journaux ?',
    'Couldn\'t save settings.' => 'Impossible d\'enregistrer les paramètres.',
    'Settings saved.' => 'Paramètres enregistrés.',
    'Deleted {count} old log entries.' => '{count} anciennes entrées de journal supprimées.',

    // New settings
    'Permission Changes' => 'Modifications de permissions',
    'Log when user permissions or group assignments change.' => 'Journaliser les modifications des permissions ou des assignations de groupe des utilisateurs.',
    'Log asset uploads, replacements, and modifications.' => 'Journaliser les téléversements, remplacements et modifications de ressources.',

    // Exclusions
    'Exclusions' => 'Exclusions',
    'Specify element types and sections to exclude from logging.' => 'Spécifiez les types d\'éléments et les sections à exclure de la journalisation.',
    'Excluded Element Types' => 'Types d\'éléments exclus',
    'Select element types to exclude from audit logging.' => 'Sélectionnez les types d\'éléments à exclure de la journalisation d\'audit.',
    'Excluded Sections' => 'Sections exclues',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => 'Sélectionnez les sections à exclure de la journalisation d\'audit (s\'applique uniquement aux éléments d\'entrée).',

    // External Log Shipping
    'External Log Shipping' => 'Envoi de journaux externes',
    'Send audit logs to external services for centralized monitoring.' => 'Envoyer les journaux d\'audit à des services externes pour une surveillance centralisée.',
    'Enable External Shipping' => 'Activer l\'envoi externe',
    'Send logs to an external service via queue.' => 'Envoyer les journaux à un service externe via une file d\'attente.',
    'Provider' => 'Fournisseur',
    'Select the external logging provider.' => 'Sélectionnez le fournisseur de journalisation externe.',
    'Endpoint URL' => 'URL du point de terminaison',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'L\'URL vers laquelle envoyer les journaux (p. ex., point de terminaison HEC de Splunk, intake Datadog ou webhook personnalisé).',
    'API Key' => 'Clé API',
    'API key or token for authentication with the external service.' => 'Clé API ou jeton pour l\'authentification auprès du service externe.',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI : php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => 'Envoi du journal d\'audit à {provider}',

    // v1.1 — Date filters
    'Date From' => 'Date de début',
    'Date To' => 'Date de fin',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => 'Exécuter le nettoyage automatiquement selon un programme quotidien',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'Le nettoyage basé sur le GC s\'exécute automatiquement quoi qu\'il arrive. Cette option ajoute un programme quotidien prévisible via la file d\'attente.',
    'Scheduled audit log retention cleanup' => 'Nettoyage planifié de rétention des journaux d\'audit',

    // v1.1 — Dashboard widget
    'Audit Activity' => 'Activité d\'audit',
    'Events' => 'Événements',
    'Insufficient permissions to view audit data.' => 'Permissions insuffisantes pour consulter les données d\'audit.',
    'Lookback Period' => 'Période d\'observation',
    '7 days' => '7 jours',
    '14 days' => '14 jours',
    '30 days' => '30 jours',
    'View all logs →' => 'Voir tous les journaux →',

    // v1.2 — Pagination
    'Per page' => 'Par page',
    'Page {current} of {total}' => 'Page {current} sur {total}',
    '« First' => '« Premier',
    '‹ Prev' => '‹ Préc.',
    'Next ›' => 'Suiv. ›',
    'Last »' => 'Dernier »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => 'Envoi de {count} journaux d\'audit à {provider}',

    // v1.3 — Integrity check
    'Integrity Check' => 'Vérification d\'intégrité',
    'Log Integrity Verification' => 'Vérification de l\'intégrité des journaux',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'Parcourt chaque journal d\'audit et vérifie son hash d\'intégrité HMAC-SHA256. Les enregistrements altérés sont signalés ici et via la CLI.',
    'Last Run' => 'Dernière exécution',
    'Run At' => 'Exécuté le',
    'Verified' => 'Vérifié',
    'Tampered' => 'Altéré',
    'None' => 'Aucun',
    'records' => 'enregistrements',
    'IDs:' => 'IDs :',
    'No verification runs yet.' => 'Aucune vérification effectuée pour le moment.',
    'Verify All Logs Now' => 'Vérifier tous les journaux maintenant',
    'May take a few minutes for large log tables.' => 'Peut prendre quelques minutes pour les grandes tables de journaux.',
    'Verified — record integrity intact' => 'Vérifié — intégrité de l\'enregistrement intacte',
    'WARNING — record may have been tampered with' => 'ATTENTION — l\'enregistrement a peut-être été altéré',
    'Not verified' => 'Non vérifié',
    'Verification' => 'Vérification',
    'Verification complete. {count} tampered records found.' => 'Vérification terminée. {count} enregistrements altérés trouvés.',
    'All {total} logs verified OK.' => 'Les {total} journaux ont été vérifiés avec succès.',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Secret du webhook',
    'Webhook Signature' => 'Signature du webhook',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => 'Signer les charges utiles des webhooks sortants avec un secret partagé pour que les destinataires puissent vérifier l\'authenticité.',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Secret partagé optionnel pour signer les charges utiles des webhooks. Supporte la syntaxe $ENV_VAR.',

    // v1.3 — Settings navigation & descriptions
    'General' => 'Général',
    'Logging' => 'Journalisation',
    'Alerts' => 'Alertes',
    'External Shipping' => 'Envoi externe',
    'Alert Settings' => 'Paramètres d\'alertes',
    'Logging Settings' => 'Paramètres de journalisation',
    'Configure basic audit trail settings.' => 'Configurer les paramètres de base du journal d\'audit.',
    'Configure which events should be tracked.' => 'Configurer les événements à suivre.',
    'Configure email notifications for suspicious activity.' => 'Configurer les notifications par e-mail pour les activités suspectes.',
    'Configure what additional data to capture with each audit event.' => 'Configurer les données supplémentaires à capturer avec chaque événement d\'audit.',

    // v1.3 — Log view extras
    'Location' => 'Emplacement',
    'Element' => 'Élément',
    'No changes detected.' => 'Aucune modification détectée.',
    'Field' => 'Champ',
    'Old' => 'Ancien',
    'New' => 'Nouveau',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'Géolocalisation IP',
    'Enable geolocation' => 'Activer la géolocalisation',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => 'Résoudre les adresses IP en pays/région/ville via une API externe. Ajoute un léger délai par écriture de journal.',
    'Geolocation endpoint' => 'Point de terminaison de géolocalisation',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'Modèle d\'URL. Par défaut : http://ip-api.com/json/ (gratuit, limité en débit).',

    // v1.3 — Export validation
    'Invalid "from" date format.' => 'Format de date « de début » invalide.',
    'Invalid "to" date format.' => 'Format de date « de fin » invalide.',
    '"From" date must be before "to" date.' => 'La date « de début » doit être antérieure à la date « de fin ».',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => 'La rétention est configurée pour conserver les journaux indéfiniment. Sans tâche de nettoyage planifiée, la base de données croîtra indéfiniment. Envisagez de définir une durée de rétention ou de planifier `php craft trails/retention/cleanup` via cron.',
];
