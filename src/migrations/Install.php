<?php

namespace anvildev\trails\migrations;

use craft\db\Migration;
use craft\helpers\StringHelper;

/**
 * Install migration that creates the full Trails 1.0.0 schema.
 */
class Install extends Migration
{
    public function safeUp(): bool
    {
        // ─── trails_logs ─────────────────────────────────────────────────────
        // Column order matches the order produced by the historical
        // incremental-migration sequence so partition rotations (which clone
        // CREATE TABLE statements) keep emitting an identical layout.
        $this->createTable('{{%trails_logs}}', [
            'id' => $this->primaryKey(),
            'event' => $this->string(100)->notNull(),
            'category' => $this->string(50),
            'elementType' => $this->string(255),
            'elementId' => $this->integer(),
            'elementTitle' => $this->string(255),
            'userId' => $this->integer(),
            'userName' => $this->string(255),
            'userEmail' => $this->string(512),
            'ipAddress' => $this->string(45),
            'country' => $this->string(2)->null(),
            'region' => $this->string(100)->null(),
            'city' => $this->string(100)->null(),
            'userAgent' => $this->string(500),
            'requestUrl' => $this->string(255),
            'requestMethod' => $this->string(10),
            'siteId' => $this->integer(),
            'oldValue' => $this->text(),
            'newValue' => $this->text(),
            'metadata' => $this->text(),
            'sessionId' => $this->string(64),
            'hash' => $this->string(80),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'chainPosition' => $this->bigInteger()->unsigned()->null(),
            'prevHash' => $this->string(128)->null(),
            'merkleRootId' => $this->integer()->null(),
        ]);

        $logIndexes = [
            'chainPosition' => ['columns' => 'chainPosition', 'unique' => true],
            'event' => ['columns' => 'event', 'unique' => false],
            'category' => ['columns' => 'category', 'unique' => false],
            'userId' => ['columns' => 'userId', 'unique' => false],
            'elementId' => ['columns' => 'elementId', 'unique' => false],
            'dateCreated' => ['columns' => 'dateCreated', 'unique' => false],
            'event_dateCreated' => ['columns' => ['event', 'dateCreated'], 'unique' => false],
            'ipAddress' => ['columns' => 'ipAddress', 'unique' => false],
            'sessionId' => ['columns' => 'sessionId', 'unique' => false],
            'userName' => ['columns' => 'userName', 'unique' => false],
            'elementTitle' => ['columns' => 'elementTitle', 'unique' => false],
            'userId_dateCreated' => ['columns' => ['userId', 'dateCreated'], 'unique' => false],
            'elementId_elementType' => ['columns' => ['elementId', 'elementType'], 'unique' => false],
        ];

        foreach ($logIndexes as $name => $def) {
            $this->createIndex(
                "idx_trails_logs_{$name}",
                '{{%trails_logs}}',
                $def['columns'],
                $def['unique']
            );
        }

        // ─── trails_log_months ───────────────────────────────────────────────
        // Monthly partition registry. The active row is always `trails_logs`;
        // archived rows reference the rotated `trails_logs_YYYY_MM` tables.
        $this->createTable('{{%trails_log_months}}', [
            'id' => $this->primaryKey(),
            'tableName' => $this->string(64)->notNull(),
            'dateFrom' => $this->date()->notNull(),
            'dateTo' => $this->date()->notNull(),
            'rowCount' => $this->integer()->notNull()->defaultValue(0),
            'firstChainPosition' => $this->bigInteger()->unsigned()->null(),
            'lastChainPosition' => $this->bigInteger()->unsigned()->null(),
            'status' => $this->string(16)->notNull()->defaultValue('active'),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        // Initial active month registry entry.
        $now = date('Y-m-d H:i:s');
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');

        $this->insert('{{%trails_log_months}}', [
            'tableName' => 'trails_logs',
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'rowCount' => 0,
            'firstChainPosition' => null,
            'lastChainPosition' => null,
            'status' => 'active',
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => StringHelper::UUID(),
        ]);

        // ─── trails_merkle_roots ─────────────────────────────────────────────
        $this->createTable('{{%trails_merkle_roots}}', [
            'id' => $this->primaryKey(),
            'batchStartPosition' => $this->bigInteger()->unsigned()->notNull(),
            'batchEndPosition' => $this->bigInteger()->unsigned()->notNull(),
            'recordCount' => $this->integer()->notNull(),
            'rootHash' => $this->string(128)->notNull(),
            'tableName' => $this->string(64)->notNull(),
            'dateComputed' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex(
            'idx_merkle_batch',
            '{{%trails_merkle_roots}}',
            ['batchStartPosition', 'batchEndPosition']
        );

        // ─── trails_anchors ──────────────────────────────────────────────────
        // External proofs (S3 Object Lock / RFC 3161 TSA) for Merkle roots.
        $this->createTable('{{%trails_anchors}}', [
            'id' => $this->primaryKey(),
            'merkleRootId' => $this->integer()->notNull(),
            'anchorType' => $this->string(16)->notNull(),
            'anchorRef' => $this->text()->notNull(),
            'anchorProof' => $this->binary()->null(),
            'verified' => $this->boolean()->notNull()->defaultValue(false),
            'dateAnchored' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex('idx_anchor_rootId', '{{%trails_anchors}}', 'merkleRootId');
        $this->addForeignKey(
            'fk_anchor_merkleRoot',
            '{{%trails_anchors}}',
            'merkleRootId',
            '{{%trails_merkle_roots}}',
            'id',
            'CASCADE'
        );

        // ─── trails_exports ──────────────────────────────────────────────────
        // Background export job tracking.
        $this->createTable('{{%trails_exports}}', [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'status' => $this->string(16)->notNull()->defaultValue('pending'),
            'format' => $this->string(8)->notNull(),
            'filePath' => $this->string(512)->null(),
            'totalRecords' => $this->integer()->null(),
            'progress' => $this->smallInteger()->notNull()->defaultValue(0),
            'criteria' => $this->text()->null(),
            'dateExpires' => $this->dateTime()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex('idx_trails_exports_userId', '{{%trails_exports}}', 'userId');
        $this->createIndex('idx_trails_exports_status', '{{%trails_exports}}', 'status');
        $this->createIndex('idx_trails_exports_dateExpires', '{{%trails_exports}}', 'dateExpires');

        // ─── trails_api_tokens ───────────────────────────────────────────────
        // Bearer-token auth for the REST API.
        $this->createTable('{{%trails_api_tokens}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'tokenHash' => $this->string(64)->notNull(),
            'scopes' => $this->text()->null(),
            'createdByUserId' => $this->integer()->null(),
            'lastUsedAt' => $this->dateTime()->null(),
            'expiresAt' => $this->dateTime()->null(),
            'revokedAt' => $this->dateTime()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex(
            'idx_trails_api_tokens_tokenHash_unique',
            '{{%trails_api_tokens}}',
            'tokenHash',
            true
        );
        $this->createIndex('idx_trails_api_tokens_revokedAt', '{{%trails_api_tokens}}', 'revokedAt');
        $this->createIndex('idx_trails_api_tokens_expiresAt', '{{%trails_api_tokens}}', 'expiresAt');

        return true;
    }

    public function safeDown(): bool
    {
        // Drop child tables first (FK to trails_merkle_roots).
        $this->dropTableIfExists('{{%trails_anchors}}');
        $this->dropTableIfExists('{{%trails_api_tokens}}');
        $this->dropTableIfExists('{{%trails_exports}}');
        $this->dropTableIfExists('{{%trails_merkle_roots}}');
        $this->dropTableIfExists('{{%trails_log_months}}');

        // Drop archive tables created at runtime by TableRotationService
        // (named `trails_logs_YYYY_MM`). Iterate the schema rather than the
        // (now-being-dropped) registry so an orphaned archive table can't
        // strand the uninstall.
        $schema = $this->db->getSchema();
        $prefix = $schema->getRawTableName('{{%trails_logs}}') . '_';
        foreach ($schema->getTableNames() as $tableName) {
            if (str_starts_with($tableName, $prefix)) {
                $this->dropTableIfExists($tableName);
            }
        }

        $this->dropTableIfExists('{{%trails_logs}}');

        return true;
    }
}
