<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'Registros de actividad',
    'Export' => 'Exportar',
    'Settings' => 'Configuración',

    // Permissions
    'View audit logs' => 'Ver registros de auditoría',
    'Export audit logs' => 'Exportar registros de auditoría',
    'Manage settings' => 'Gestionar configuración',

    // Logs
    'Log Details' => 'Detalles del registro',
    'Back to Logs' => 'Volver a registros',
    'No audit logs found.' => 'No se encontraron registros de auditoría.',
    'Showing {count} of {total} logs' => 'Mostrando {count} de {total} registros',
    'View' => 'Ver',
    'Filter' => 'Filtrar',
    'Reset' => 'Restablecer',

    // Summary
    'Events (7d)' => 'Eventos (7d)',
    'Logins' => 'Inicios de sesión',
    'Created' => 'Creados',
    'Updated' => 'Actualizados',
    'Deleted' => 'Eliminados',

    // Filters
    'Event Type' => 'Tipo de evento',
    'Category' => 'Categoría',
    'User' => 'Usuario',
    'Search' => 'Buscar',
    'Search logs...' => 'Buscar registros...',
    'All Events' => 'Todos los eventos',
    'All Categories' => 'Todas las categorías',
    'All Users' => 'Todos los usuarios',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'Marca de tiempo',
    'Event' => 'Evento',
    'User Information' => 'Información del usuario',
    'User ID' => 'ID de usuario',
    'Username' => 'Nombre de usuario',
    'Email' => 'Correo electrónico',
    'IP Address' => 'Dirección IP',
    'Session ID' => 'ID de sesión',
    'Element Information' => 'Información del elemento',
    'Element Type' => 'Tipo de elemento',
    'Element ID' => 'ID de elemento',
    'Element Title' => 'Título del elemento',
    'Site ID' => 'ID del sitio',
    'Request Information' => 'Información de la solicitud',
    'Request Method' => 'Método de solicitud',
    'Request URL' => 'URL de solicitud',
    'User Agent' => 'Agente de usuario',
    'Metadata' => 'Metadatos',
    'Changes' => 'Cambios',
    'Previous Value' => 'Valor anterior',
    'New Value' => 'Nuevo valor',
    'Integrity' => 'Integridad',
    'Hash' => 'Hash',

    // Pagination
    '← Previous' => '← Anterior',
    'Next →' => 'Siguiente →',

    // Export
    'Export Logs' => 'Exportar registros',
    'Export Audit Logs' => 'Exportar registros de auditoría',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'Exporte sus registros de auditoría para informes de cumplimiento, copias de seguridad o análisis.',
    'From Date' => 'Desde fecha',
    'To Date' => 'Hasta fecha',
    'Leave empty to include all history' => 'Dejar vacío para incluir todo el historial',
    'Leave empty to include up to now' => 'Dejar vacío para incluir hasta ahora',
    'Export Format' => 'Formato de exportación',
    'Choose the format for your export file' => 'Elija el formato para su archivo de exportación',
    'Download Export' => 'Descargar exportación',
    // Settings
    'Trails Settings' => 'Configuración de Trails',
    'General Settings' => 'Configuración general',
    'Enable Logging' => 'Activar registro',
    'When disabled, no new audit events will be recorded.' => 'Cuando está desactivado, no se registrarán nuevos eventos de auditoría.',
    'Retention Period (Days)' => 'Período de retención (días)',
    'How long to keep logs. Set to 0 to keep forever.' => 'Tiempo de conservación de los registros. Establezca 0 para conservarlos indefinidamente.',
    'Current Storage' => 'Almacenamiento actual',
    'logs' => 'registros',
    'from' => 'desde',
    '{count} logs scheduled for cleanup' => '{count} registros programados para limpieza',
    'What to Log' => 'Qué registrar',
    'Element Changes' => 'Cambios en elementos',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'Registrar cuando se crean, actualizan o eliminan entradas, recursos, usuarios y otros elementos.',
    'User Authentication' => 'Autenticación de usuarios',
    'Log user logins and logouts.' => 'Registrar inicios y cierres de sesión de usuarios.',
    'Failed Login Attempts' => 'Intentos de inicio de sesión fallidos',
    'Log failed authentication attempts for security monitoring.' => 'Registrar intentos de autenticación fallidos para supervisión de seguridad.',
    'Config Changes' => 'Cambios de configuración',
    'Log when project config changes are applied.' => 'Registrar cuando se aplican cambios en la configuración del proyecto.',
    'Asset Operations' => 'Operaciones con recursos',
    'Log asset uploads, deletions, and modifications.' => 'Registrar subidas, eliminaciones y modificaciones de recursos.',
    'Data Capture' => 'Captura de datos',
    'Capture IP Addresses' => 'Capturar direcciones IP',
    'Store the IP address of each action.' => 'Almacenar la dirección IP de cada acción.',
    'Anonymize IPs (GDPR)' => 'Anonimizar IPs (RGPD)',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'Reemplazar el último octeto de las direcciones IP por 0 para cumplir con la normativa de privacidad.',
    'Capture User Agent' => 'Capturar agente de usuario',
    'Store the browser/device information.' => 'Almacenar la información del navegador/dispositivo.',
    'Capture Field Changes' => 'Capturar cambios de campo',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'Almacenar los valores antes/después de los cambios de campo. Advertencia: aumenta el uso de almacenamiento.',
    'This can significantly increase database size.' => 'Esto puede aumentar significativamente el tamaño de la base de datos.',
    'Email Alerts' => 'Alertas por correo electrónico',
    'Enable Alerts' => 'Activar alertas',
    'Send email notifications for suspicious activity.' => 'Enviar notificaciones por correo electrónico para actividad sospechosa.',
    'Alert Email' => 'Correo electrónico de alerta',
    'Email address to receive security alerts.' => 'Dirección de correo electrónico para recibir alertas de seguridad.',
    'Failed Login Threshold' => 'Umbral de intentos fallidos',
    'Send alert after this many failed logins from the same IP within 1 hour.' => 'Enviar alerta después de este número de inicios de sesión fallidos desde la misma IP en 1 hora.',
    'Save Settings' => 'Guardar configuración',
    'Maintenance' => 'Mantenimiento',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => 'Ejecutar la limpieza de retención manualmente para eliminar registros antiguos según su política de retención.',
    'Run Cleanup Now' => 'Ejecutar limpieza ahora',
    'Are you sure you want to delete old logs?' => '¿Está seguro de que desea eliminar los registros antiguos?',
    'Couldn\'t save settings.' => 'No se pudo guardar la configuración.',
    'Settings saved.' => 'Configuración guardada.',
    'Deleted {count} old log entries.' => 'Se eliminaron {count} entradas de registro antiguas.',

    // New settings
    'Permission Changes' => 'Cambios de permisos',
    'Log when user permissions or group assignments change.' => 'Registrar cuando cambien los permisos de usuario o las asignaciones de grupo.',
    'Log asset uploads, replacements, and modifications.' => 'Registrar subidas, reemplazos y modificaciones de recursos.',

    // Exclusions
    'Exclusions' => 'Exclusiones',
    'Specify element types and sections to exclude from logging.' => 'Especifique los tipos de elementos y secciones a excluir del registro.',
    'Excluded Element Types' => 'Tipos de elementos excluidos',
    'Select element types to exclude from audit logging.' => 'Seleccione los tipos de elementos a excluir del registro de auditoría.',
    'Excluded Sections' => 'Secciones excluidas',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => 'Seleccione las secciones a excluir del registro de auditoría (solo aplica a elementos de entrada).',

    // External Log Shipping
    'External Log Shipping' => 'Envío de registros externos',
    'Send audit logs to external services for centralized monitoring.' => 'Enviar registros de auditoría a servicios externos para supervisión centralizada.',
    'Enable External Shipping' => 'Activar envío externo',
    'Send logs to an external service via queue.' => 'Enviar registros a un servicio externo mediante cola.',
    'Provider' => 'Proveedor',
    'Select the external logging provider.' => 'Seleccione el proveedor de registro externo.',
    'Endpoint URL' => 'URL del endpoint',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'La URL a la que enviar los registros (p. ej., endpoint HEC de Splunk, intake de Datadog o webhook personalizado).',
    'API Key' => 'Clave API',
    'API key or token for authentication with the external service.' => 'Clave API o token para autenticación con el servicio externo.',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI: php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => 'Enviando registro de auditoría a {provider}',

    // v1.1 — Date filters
    'Date From' => 'Fecha desde',
    'Date To' => 'Fecha hasta',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => 'Ejecutar limpieza automáticamente con un programa diario',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'La limpieza basada en GC se ejecuta automáticamente de todos modos. Esta opción agrega un programa diario predecible mediante la cola.',
    'Scheduled audit log retention cleanup' => 'Limpieza programada de retención de registros de auditoría',

    // v1.1 — Dashboard widget
    'Audit Activity' => 'Actividad de auditoría',
    'Events' => 'Eventos',
    'Insufficient permissions to view audit data.' => 'Permisos insuficientes para ver los datos de auditoría.',
    'Lookback Period' => 'Período de consulta',
    '7 days' => '7 días',
    '14 days' => '14 días',
    '30 days' => '30 días',
    'View all logs →' => 'Ver todos los registros →',

    // v1.2 — Pagination
    'Per page' => 'Por página',
    'Page {current} of {total}' => 'Página {current} de {total}',
    '« First' => '« Primera',
    '‹ Prev' => '‹ Anterior',
    'Next ›' => 'Siguiente ›',
    'Last »' => 'Última »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => 'Enviando {count} registros de auditoría a {provider}',

    // v1.3 — Integrity check
    'Integrity Check' => 'Verificación de integridad',
    'Log Integrity Verification' => 'Verificación de integridad de registros',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'Recorre cada registro de auditoría y verifica su hash de integridad HMAC-SHA256. Los registros alterados se informan aquí y a través de la CLI.',
    'Last Run' => 'Última ejecución',
    'Run At' => 'Ejecutado el',
    'Verified' => 'Verificado',
    'Tampered' => 'Alterado',
    'None' => 'Ninguno',
    'records' => 'registros',
    'IDs:' => 'IDs:',
    'No verification runs yet.' => 'Aún no se han realizado verificaciones.',
    'Verify All Logs Now' => 'Verificar todos los registros ahora',
    'May take a few minutes for large log tables.' => 'Puede tardar unos minutos para tablas de registros grandes.',
    'Verified — record integrity intact' => 'Verificado — integridad del registro intacta',
    'WARNING — record may have been tampered with' => 'ADVERTENCIA — el registro puede haber sido alterado',
    'Not verified' => 'No verificado',
    'Verification' => 'Verificación',
    'Verification complete. {count} tampered records found.' => 'Verificación completada. Se encontraron {count} registros alterados.',
    'All {total} logs verified OK.' => 'Los {total} registros verificados correctamente.',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Secreto del webhook',
    'Webhook Signature' => 'Firma del webhook',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => 'Firmar las cargas útiles de webhooks salientes con un secreto compartido para que los destinatarios puedan verificar la autenticidad.',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Secreto compartido opcional para firmar las cargas útiles de webhooks. Soporta la sintaxis $ENV_VAR.',

    // v1.3 — Settings navigation & descriptions
    'General' => 'General',
    'Logging' => 'Registro',
    'Alerts' => 'Alertas',
    'External Shipping' => 'Envío externo',
    'Alert Settings' => 'Configuración de alertas',
    'Logging Settings' => 'Configuración de registro',
    'Configure basic audit trail settings.' => 'Configurar los ajustes básicos del registro de auditoría.',
    'Configure which events should be tracked.' => 'Configurar qué eventos deben rastrearse.',
    'Configure email notifications for suspicious activity.' => 'Configurar notificaciones por correo electrónico para actividad sospechosa.',
    'Configure what additional data to capture with each audit event.' => 'Configurar qué datos adicionales capturar con cada evento de auditoría.',

    // v1.3 — Log view extras
    'Location' => 'Ubicación',
    'Element' => 'Elemento',
    'No changes detected.' => 'No se detectaron cambios.',
    'Field' => 'Campo',
    'Old' => 'Anterior',
    'New' => 'Nuevo',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'Geolocalización IP',
    'Enable geolocation' => 'Activar geolocalización',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => 'Resolver direcciones IP a país/región/ciudad mediante una API externa. Añade un pequeño retraso por cada escritura de registro.',
    'Geolocation endpoint' => 'Endpoint de geolocalización',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'Plantilla de URL. Por defecto: http://ip-api.com/json/ (gratuito, con límite de velocidad).',

    // v1.3 — Export validation
    'Invalid "from" date format.' => 'Formato de fecha «desde» no válido.',
    'Invalid "to" date format.' => 'Formato de fecha «hasta» no válido.',
    '"From" date must be before "to" date.' => 'La fecha «desde» debe ser anterior a la fecha «hasta».',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => 'La retención está configurada para conservar los registros indefinidamente. Sin una tarea de limpieza programada, la base de datos crecerá indefinidamente. Considere establecer un período de retención o programar `php craft trails/retention/cleanup` mediante cron.',
];
