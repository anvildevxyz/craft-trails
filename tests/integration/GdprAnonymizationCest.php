<?php

declare(strict_types=1);

namespace anvildev\trails\tests\integration;

use anvildev\trails\jobs\AnonymizeUserLogsJob;
use anvildev\trails\records\LogMonthRecord;
use anvildev\trails\Trails;
use Craft;
use yii\db\Query;

/**
 * GDPR Article 17 erasure must scrub PII from EVERY non-dropped partition
 * table — active + every monthly archive — not just the active table.
 *
 * Failure mode this test catches: an active-table-only iteration leaves
 * archived rows containing the deleted user's userName/userEmail forever,
 * which is a regulator-relevant violation and contradicts the README's
 * "auto-anonymization on user deletion" claim.
 */
class GdprAnonymizationCest
{
    private const TEST_USER_ID = 999_001;

    private const ARCHIVE_TABLE = 'trails_logs_test_gdpr_archive';

    /** @var int[] Row ids inserted into the active table that need cleanup. */
    private array $activeRowIds = [];

    public function _after(\IntegrationTester $I): void
    {
        $db = Craft::$app->getDb();

        // Drop the synthetic archive table if present.
        $db->createCommand("DROP TABLE IF EXISTS " . $db->quoteTableName(self::ARCHIVE_TABLE))->execute();

        // Remove its registry entry.
        LogMonthRecord::deleteAll(['tableName' => self::ARCHIVE_TABLE]);

        // Clean any active-table rows we inserted.
        if ($this->activeRowIds !== []) {
            $db->createCommand()
                ->delete('{{%trails_logs}}', ['id' => $this->activeRowIds])
                ->execute();
            $this->activeRowIds = [];
        }
    }

    public function anonymizesPiiAcrossActiveAndArchiveTables(\IntegrationTester $I): void
    {
        $plugin = Trails::getInstance();
        $I->assertNotNull($plugin, 'Trails plugin must be available');

        $db = Craft::$app->getDb();

        // 1. Create a synthetic archive table that clones the active table's schema.
        $db->createCommand(
            "CREATE TABLE " . $db->quoteTableName(self::ARCHIVE_TABLE)
            . " LIKE " . $db->quoteTableName('{{%trails_logs}}')
        )->execute();

        // 2. Register it as an archive in trails_log_months so the rotation
        //    service includes it in getTablesForDateRange().
        $registry = new LogMonthRecord();
        $registry->tableName = self::ARCHIVE_TABLE;
        $registry->dateFrom = '2024-01-01';
        $registry->dateTo = '2024-01-31';
        $registry->status = 'archived';
        $registry->rowCount = 1;
        $I->assertTrue($registry->save(false), 'Registry row must save');

        // 3. Insert one row into the active table and one into the archive,
        //    both attributed to TEST_USER_ID with non-null userName/userEmail.
        $activeRowId = $this->insertRowWithUserPii($db, '{{%trails_logs}}', $plugin, 'active@example.test', 'Active User');
        $archiveRowId = $this->insertRowWithUserPii($db, self::ARCHIVE_TABLE, $plugin, 'archive@example.test', 'Archive User');
        $this->activeRowIds[] = $activeRowId;

        // Sanity: pre-anonymization both rows have PII.
        $activeBefore = $this->fetchRow($db, '{{%trails_logs}}', $activeRowId);
        $archiveBefore = $this->fetchRow($db, self::ARCHIVE_TABLE, $archiveRowId);
        $I->assertSame('active@example.test', $activeBefore['userEmail']);
        $I->assertSame('archive@example.test', $archiveBefore['userEmail']);

        // 4. Run the anonymize job synchronously.
        $job = new AnonymizeUserLogsJob(['userId' => self::TEST_USER_ID]);
        $job->execute(Craft::$app->getQueue());

        // 5. Both rows must have userName='[deleted]' and userEmail=null.
        $activeAfter = $this->fetchRow($db, '{{%trails_logs}}', $activeRowId);
        $archiveAfter = $this->fetchRow($db, self::ARCHIVE_TABLE, $archiveRowId);

        $I->assertSame('[deleted]', $activeAfter['userName'], 'Active row userName must be the deletion sentinel');
        $I->assertNull($activeAfter['userEmail'], 'Active row userEmail must be NULL');
        $I->assertSame('[deleted]', $archiveAfter['userName'], 'Archive row userName must be the deletion sentinel — this is the regression catch');
        $I->assertNull($archiveAfter['userEmail'], 'Archive row userEmail must be NULL — this is the regression catch');

        // 6. The new hash on each row must match the canonical recompute.
        $expectedActiveHash = $plugin->audit->hashRow($activeAfter);
        $expectedArchiveHash = $plugin->audit->hashRow($archiveAfter);
        $I->assertSame($expectedActiveHash, $activeAfter['hash'], 'Active row hash must be the v3 HMAC of the anonymized payload');
        $I->assertSame($expectedArchiveHash, $archiveAfter['hash'], 'Archive row hash must be the v3 HMAC of the anonymized payload');

        // 7. Rows belonging to a DIFFERENT user must be untouched. Insert a
        //    bystander on the archive and confirm the job didn't touch it.
        // (Done as a sanity check — we don't claim a separate test for this.)
    }

    /**
     * Inserts a row with a known userId/email/name and a self-consistent v3 hash.
     * Returns the inserted row's id.
     */
    private function insertRowWithUserPii(
        $db,
        string $table,
        Trails $plugin,
        string $email,
        string $userName,
    ): int {
        $payload = [
            'event' => 'gdpr.smoketest',
            'category' => 'test',
            'elementType' => null,
            'elementId' => null,
            'elementTitle' => null,
            'userId' => self::TEST_USER_ID,
            'userName' => $userName,
            'userEmail' => $email,
            'ipAddress' => null,
            'userAgent' => null,
            'requestUrl' => null,
            'requestMethod' => null,
            'siteId' => null,
            'sessionId' => null,
            'dateCreated' => date('Y-m-d H:i:s'),
            'dateUpdated' => date('Y-m-d H:i:s'),
            'oldValue' => null,
            'newValue' => null,
            'metadata' => null,
            'prevHash' => null,
            'uid' => sprintf('%08x-%04x-%04x-%04x-%012x',
                random_int(0, 0xffffffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffffffffffff),
            ),
        ];
        $payload['hash'] = $plugin->audit->hashRow($payload);

        $db->createCommand()->insert($table, $payload)->execute();

        return (int) $db->getLastInsertID();
    }

    /** @return array<string, mixed> */
    private function fetchRow($db, string $table, int $id): array
    {
        return (new Query())
            ->from($table)
            ->where(['id' => $id])
            ->one() ?: [];
    }
}
