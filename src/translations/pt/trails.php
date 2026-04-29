<?php

return [
    // Navigation
    'Trails' => 'Trails',
    'Activity Logs' => 'Registos de atividade',
    'Export' => 'Exportar',
    'Settings' => 'Definições',

    // Permissions
    'View audit logs' => 'Ver registos de auditoria',
    'Export audit logs' => 'Exportar registos de auditoria',
    'Manage settings' => 'Gerir definições',

    // Logs
    'Log Details' => 'Detalhes do registo',
    'Back to Logs' => 'Voltar aos registos',
    'No audit logs found.' => 'Nenhum registo de auditoria encontrado.',
    'Showing {count} of {total} logs' => 'A mostrar {count} de {total} registos',
    'View' => 'Ver',
    'Filter' => 'Filtrar',
    'Reset' => 'Repor',

    // Summary
    'Events (7d)' => 'Eventos (7d)',
    'Logins' => 'Inícios de sessão',
    'Created' => 'Criados',
    'Updated' => 'Atualizados',
    'Deleted' => 'Eliminados',

    // Filters
    'Event Type' => 'Tipo de evento',
    'Category' => 'Categoria',
    'User' => 'Utilizador',
    'Search' => 'Pesquisar',
    'Search logs...' => 'Pesquisar registos...',
    'All Events' => 'Todos os eventos',
    'All Categories' => 'Todas as categorias',
    'All Users' => 'Todos os utilizadores',

    // Log details
    'ID' => 'ID',
    'Timestamp' => 'Carimbo de data/hora',
    'Event' => 'Evento',
    'User Information' => 'Informações do utilizador',
    'User ID' => 'ID do utilizador',
    'Username' => 'Nome de utilizador',
    'Email' => 'E-mail',
    'IP Address' => 'Endereço IP',
    'Session ID' => 'ID de sessão',
    'Element Information' => 'Informações do elemento',
    'Element Type' => 'Tipo de elemento',
    'Element ID' => 'ID do elemento',
    'Element Title' => 'Título do elemento',
    'Site ID' => 'ID do site',
    'Request Information' => 'Informações do pedido',
    'Request Method' => 'Método do pedido',
    'Request URL' => 'URL do pedido',
    'User Agent' => 'Agente do utilizador',
    'Metadata' => 'Metadados',
    'Changes' => 'Alterações',
    'Previous Value' => 'Valor anterior',
    'New Value' => 'Novo valor',
    'Integrity' => 'Integridade',
    'Hash' => 'Hash',

    // Pagination
    '← Previous' => '← Anterior',
    'Next →' => 'Seguinte →',

    // Export
    'Export Logs' => 'Exportar registos',
    'Export Audit Logs' => 'Exportar registos de auditoria',
    'Export your audit logs for compliance reporting, backup, or analysis.' => 'Exporte os seus registos de auditoria para relatórios de conformidade, cópia de segurança ou análise.',
    'From Date' => 'Data de início',
    'To Date' => 'Data de fim',
    'Leave empty to include all history' => 'Deixar em branco para incluir todo o histórico',
    'Leave empty to include up to now' => 'Deixar em branco para incluir até ao momento atual',
    'Export Format' => 'Formato de exportação',
    'Choose the format for your export file' => 'Escolha o formato para o seu ficheiro de exportação',
    'Download Export' => 'Transferir exportação',
    // Settings
    'Trails Settings' => 'Definições do Trails',
    'General Settings' => 'Definições gerais',
    'Enable Logging' => 'Ativar registo',
    'When disabled, no new audit events will be recorded.' => 'Quando desativado, não serão registados novos eventos de auditoria.',
    'Retention Period (Days)' => 'Período de retenção (dias)',
    'How long to keep logs. Set to 0 to keep forever.' => 'Durante quanto tempo manter os registos. Defina como 0 para manter indefinidamente.',
    'Current Storage' => 'Armazenamento atual',
    'logs' => 'registos',
    'from' => 'desde',
    '{count} logs scheduled for cleanup' => '{count} registos agendados para limpeza',
    'What to Log' => 'O que registar',
    'Element Changes' => 'Alterações de elementos',
    'Log when entries, assets, users, and other elements are created, updated, or deleted.' => 'Registar quando entradas, recursos, utilizadores e outros elementos são criados, atualizados ou eliminados.',
    'User Authentication' => 'Autenticação de utilizadores',
    'Log user logins and logouts.' => 'Registar inícios e términos de sessão dos utilizadores.',
    'Failed Login Attempts' => 'Tentativas de início de sessão falhadas',
    'Log failed authentication attempts for security monitoring.' => 'Registar tentativas de autenticação falhadas para monitorização de segurança.',
    'Config Changes' => 'Alterações de configuração',
    'Log when project config changes are applied.' => 'Registar quando são aplicadas alterações à configuração do projeto.',
    'Asset Operations' => 'Operações de recursos',
    'Log asset uploads, deletions, and modifications.' => 'Registar carregamentos, eliminações e modificações de recursos.',
    'Data Capture' => 'Captura de dados',
    'Capture IP Addresses' => 'Capturar endereços IP',
    'Store the IP address of each action.' => 'Guardar o endereço IP de cada ação.',
    'Anonymize IPs (GDPR)' => 'Anonimizar IPs (RGPD)',
    'Replace the last octet of IP addresses with 0 for privacy compliance.' => 'Substituir o último octeto dos endereços IP por 0 para conformidade com a privacidade.',
    'Capture User Agent' => 'Capturar agente do utilizador',
    'Store the browser/device information.' => 'Guardar as informações do browser/dispositivo.',
    'Capture Field Changes' => 'Capturar alterações de campos',
    'Store before/after values of field changes. Warning: increases storage usage.' => 'Guardar os valores antes/após as alterações de campos. Aviso: aumenta o uso de armazenamento.',
    'This can significantly increase database size.' => 'Isto pode aumentar significativamente o tamanho da base de dados.',
    'Email Alerts' => 'Alertas por e-mail',
    'Enable Alerts' => 'Ativar alertas',
    'Send email notifications for suspicious activity.' => 'Enviar notificações por e-mail para atividade suspeita.',
    'Alert Email' => 'E-mail de alerta',
    'Email address to receive security alerts.' => 'Endereço de e-mail para receber alertas de segurança.',
    'Failed Login Threshold' => 'Limite de tentativas falhadas',
    'Send alert after this many failed logins from the same IP within 1 hour.' => 'Enviar alerta após este número de inícios de sessão falhados do mesmo IP no espaço de 1 hora.',
    'Save Settings' => 'Guardar definições',
    'Maintenance' => 'Manutenção',
    'Run retention cleanup manually to remove old logs based on your retention policy.' => 'Execute a limpeza de retenção manualmente para remover registos antigos com base na sua política de retenção.',
    'Run Cleanup Now' => 'Executar limpeza agora',
    'Are you sure you want to delete old logs?' => 'Tem a certeza de que pretende eliminar os registos antigos?',
    'Couldn\'t save settings.' => 'Não foi possível guardar as definições.',
    'Settings saved.' => 'Definições guardadas.',
    'Deleted {count} old log entries.' => '{count} entradas de registo antigas eliminadas.',

    // New settings
    'Permission Changes' => 'Alterações de permissões',
    'Log when user permissions or group assignments change.' => 'Registar quando as permissões de utilizador ou as atribuições de grupo são alteradas.',
    'Log asset uploads, replacements, and modifications.' => 'Registar carregamentos, substituições e modificações de recursos.',

    // Exclusions
    'Exclusions' => 'Exclusões',
    'Specify element types and sections to exclude from logging.' => 'Especifique os tipos de elementos e as secções a excluir do registo.',
    'Excluded Element Types' => 'Tipos de elementos excluídos',
    'Select element types to exclude from audit logging.' => 'Selecione os tipos de elementos a excluir do registo de auditoria.',
    'Excluded Sections' => 'Secções excluídas',
    'Select sections to exclude from audit logging (only applies to Entry elements).' => 'Selecione as secções a excluir do registo de auditoria (aplica-se apenas a elementos de entrada).',

    // External Log Shipping
    'External Log Shipping' => 'Envio de registos externos',
    'Send audit logs to external services for centralized monitoring.' => 'Enviar registos de auditoria para serviços externos para monitorização centralizada.',
    'Enable External Shipping' => 'Ativar envio externo',
    'Send logs to an external service via queue.' => 'Enviar registos para um serviço externo através de fila.',
    'Provider' => 'Fornecedor',
    'Select the external logging provider.' => 'Selecione o fornecedor de registo externo.',
    'Endpoint URL' => 'URL do endpoint',
    'The URL to send logs to (e.g., Splunk HEC endpoint, Datadog intake, or custom webhook).' => 'O URL para onde enviar os registos (ex.: endpoint HEC do Splunk, intake do Datadog ou webhook personalizado).',
    'API Key' => 'Chave de API',
    'API key or token for authentication with the external service.' => 'Chave de API ou token para autenticação com o serviço externo.',

    // CLI
    'CLI: php craft trails/retention/cleanup' => 'CLI: php craft trails/retention/cleanup',

    // Job
    'Shipping audit log to {provider}' => 'A enviar registo de auditoria para {provider}',

    // v1.1 — Date filters
    'Date From' => 'Data de início',
    'Date To' => 'Data de fim',

    // v1.1 — Scheduled retention
    'Run cleanup automatically on a daily schedule' => 'Executar limpeza automaticamente com agendamento diário',
    'GC-based cleanup runs automatically regardless. This option adds a predictable daily schedule via the queue.' => 'A limpeza baseada em GC é executada automaticamente de qualquer forma. Esta opção adiciona um agendamento diário previsível através da fila.',
    'Scheduled audit log retention cleanup' => 'Limpeza agendada de retenção de registos de auditoria',

    // v1.1 — Dashboard widget
    'Audit Activity' => 'Atividade de auditoria',
    'Events' => 'Eventos',
    'Insufficient permissions to view audit data.' => 'Permissões insuficientes para visualizar dados de auditoria.',
    'Lookback Period' => 'Período de consulta',
    '7 days' => '7 dias',
    '14 days' => '14 dias',
    '30 days' => '30 dias',
    'View all logs →' => 'Ver todos os registos →',

    // v1.2 — Pagination
    'Per page' => 'Por página',
    'Page {current} of {total}' => 'Página {current} de {total}',
    '« First' => '« Primeira',
    '‹ Prev' => '‹ Anterior',
    'Next ›' => 'Seguinte ›',
    'Last »' => 'Última »',

    // v1.2 — Batch shipping
    'Shipping {count} audit logs to {provider}' => 'A enviar {count} registos de auditoria para {provider}',

    // v1.3 — Integrity check
    'Integrity Check' => 'Verificação de integridade',
    'Log Integrity Verification' => 'Verificação de integridade dos registos',
    'Walks every audit log and verifies its HMAC-SHA256 integrity hash. Tampered records are reported here and via the CLI.' => 'Percorre cada registo de auditoria e verifica o seu hash de integridade HMAC-SHA256. Os registos adulterados são reportados aqui e através da CLI.',
    'Last Run' => 'Última execução',
    'Run At' => 'Executado em',
    'Verified' => 'Verificado',
    'Tampered' => 'Adulterado',
    'None' => 'Nenhum',
    'records' => 'registos',
    'IDs:' => 'IDs:',
    'No verification runs yet.' => 'Ainda não foram realizadas verificações.',
    'Verify All Logs Now' => 'Verificar todos os registos agora',
    'May take a few minutes for large log tables.' => 'Pode demorar alguns minutos para tabelas de registos grandes.',
    'Verified — record integrity intact' => 'Verificado — integridade do registo intacta',
    'WARNING — record may have been tampered with' => 'AVISO — o registo pode ter sido adulterado',
    'Not verified' => 'Não verificado',
    'Verification' => 'Verificação',
    'Verification complete. {count} tampered records found.' => 'Verificação concluída. {count} registos adulterados encontrados.',
    'All {total} logs verified OK.' => 'Todos os {total} registos verificados com sucesso.',

    // v1.3 — Webhook signature
    'Webhook Secret' => 'Segredo do webhook',
    'Webhook Signature' => 'Assinatura do webhook',
    'Sign outgoing webhook payloads with a shared secret so receivers can verify authenticity.' => 'Assinar os payloads de webhooks de saída com um segredo partilhado para que os destinatários possam verificar a autenticidade.',
    'Optional shared secret for signing webhook payloads. Supports $ENV_VAR syntax.' => 'Segredo partilhado opcional para assinar payloads de webhooks. Suporta a sintaxe $ENV_VAR.',

    // v1.3 — Settings navigation & descriptions
    'General' => 'Geral',
    'Logging' => 'Registo',
    'Alerts' => 'Alertas',
    'External Shipping' => 'Envio externo',
    'Alert Settings' => 'Definições de alertas',
    'Logging Settings' => 'Definições de registo',
    'Configure basic audit trail settings.' => 'Configurar as definições básicas do registo de auditoria.',
    'Configure which events should be tracked.' => 'Configurar os eventos que devem ser rastreados.',
    'Configure email notifications for suspicious activity.' => 'Configurar notificações por e-mail para atividade suspeita.',
    'Configure what additional data to capture with each audit event.' => 'Configurar os dados adicionais a capturar com cada evento de auditoria.',

    // v1.3 — Log view extras
    'Location' => 'Localização',
    'Element' => 'Elemento',
    'No changes detected.' => 'Nenhuma alteração detetada.',
    'Field' => 'Campo',
    'Old' => 'Anterior',
    'New' => 'Novo',

    // v1.3 — IP geolocation
    'IP Geolocation' => 'Geolocalização IP',
    'Enable geolocation' => 'Ativar geolocalização',
    'Resolve IP addresses to country/region/city via an external API. Adds a small delay per log write.' => 'Resolver endereços IP para país/região/cidade através de uma API externa. Adiciona um pequeno atraso por cada escrita de registo.',
    'Geolocation endpoint' => 'Endpoint de geolocalização',
    'URL template. Default: http://ip-api.com/json/ (free, rate-limited).' => 'Modelo de URL. Predefinido: http://ip-api.com/json/ (gratuito, com limite de velocidade).',

    // v1.3 — Export validation
    'Invalid "from" date format.' => 'Formato de data "de início" inválido.',
    'Invalid "to" date format.' => 'Formato de data "de fim" inválido.',
    '"From" date must be before "to" date.' => 'A data "de início" deve ser anterior à data "de fim".',

    // v1.3 — Retention warning
    'Retention is set to keep logs forever. Without a scheduled cleanup job, the database will grow indefinitely. Consider setting a retention period or scheduling `php craft trails/retention/cleanup` via cron.' => 'A retenção está definida para manter os registos indefinidamente. Sem uma tarefa de limpeza agendada, a base de dados crescerá indefinidamente. Considere definir um período de retenção ou agendar `php craft trails/retention/cleanup` via cron.',
];
