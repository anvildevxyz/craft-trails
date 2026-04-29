<?php
/**
 * Dev-only smoke-test fixture.
 *
 * Computes a Merkle root over the audit-log rows added since the last
 * existing root, and anchors it via the currently configured backend
 * (Settings::anchorType). Prints DB id + anchorRef + verify roundtrip.
 *
 * Run from the project root inside the DDEV web container:
 *   ddev exec --raw -- bash -lc 'cd /var/www/html && \
 *     php plugins/trails/tests/fixtures/anchor-current-batch.php'
 *
 * Optional flags:
 *   --seed=N      Log N synthetic events first (default: 0). Useful when
 *                 the chain has nothing newer than the last root.
 *   --quiet       No status output on success.
 *
 * Exit codes:
 *   0  anchor saved + verifies
 *   1  anchor saved but verify roundtrip failed
 *   2  no new rows since last root (with --seed=0); nothing to anchor
 *   3  computeBatch returned null (no rows in range)
 *   4  anchor() returned null (backend rejected; check Craft logs)
 *   5  no anchor backend configured (Settings::anchorType is null)
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 4);  // plugins/trails/tests/fixtures → project root

require $projectRoot . '/bootstrap.php';
require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

use anvildev\trails\records\AuditLogRecord;
use anvildev\trails\records\MerkleRootRecord;
use anvildev\trails\Trails;

$opts = getopt('', ['seed::', 'quiet']);
$seed = isset($opts['seed']) ? (int) $opts['seed'] : 0;
$quiet = isset($opts['quiet']);

$say = static function (string $msg) use ($quiet): void {
    if (!$quiet) {
        echo $msg . "\n";
    }
};

$plugin = Trails::getInstance();
if ($plugin === null) {
    fwrite(STDERR, "Trails plugin not initialized.\n");
    exit(5);
}

if ($plugin->anchor->getBackend() === null) {
    fwrite(STDERR, "No anchor backend configured. Set Settings::anchorType to 's3' or 'rfc3161' first.\n");
    exit(5);
}

if ($seed > 0) {
    for ($i = 0; $i < $seed; $i++) {
        $plugin->audit->logCustomEvent(
            'smoketest.fixture',
            'smoke',
            null,
            ['ts' => time(), 'i' => $i],
        );
    }
    $say("Seeded {$seed} synthetic event(s).");
}

$lastRoot = MerkleRootRecord::find()->orderBy(['id' => SORT_DESC])->one();
$startCp = $lastRoot ? ((int) $lastRoot->batchEndPosition) + 1 : 1;
$maxCp = (int) AuditLogRecord::find()->max('chainPosition');

if ($maxCp < $startCp) {
    fwrite(STDERR, "No new rows since last root (chainPosition >= {$startCp}). Pass --seed=N to generate synthetic events.\n");
    exit(2);
}

$say("Batch range: chainPosition {$startCp}..{$maxCp}");

$root = $plugin->merkle->computeBatch($startCp, $maxCp);
if ($root === null) {
    fwrite(STDERR, "computeBatch({$startCp}, {$maxCp}) returned null.\n");
    exit(3);
}
$say("Merkle root #{$root->id}: rootHash={$root->rootHash} (recordCount={$root->recordCount})");

$anchor = $plugin->anchor->anchor($root);
if ($anchor === null) {
    fwrite(STDERR, "AnchorService::anchor() returned null. Check Craft logs.\n");
    exit(4);
}
$say("Anchor #{$anchor->id} saved: type={$anchor->anchorType}, ref={$anchor->anchorRef}, verified={$anchor->verified}");

$backend = $plugin->anchor->getBackendByType((string) $anchor->anchorType);
if ($backend === null) {
    fwrite(STDERR, "Cannot resolve backend for anchorType '{$anchor->anchorType}' to verify roundtrip.\n");
    exit(1);
}

$verified = $backend->verify(
    (string) $anchor->anchorRef,
    (string) $anchor->anchorProof,
    (string) $root->rootHash,
);

if (!$verified) {
    fwrite(STDERR, "Anchor saved but verify roundtrip FAILED for anchor #{$anchor->id}.\n");
    exit(1);
}

$say("Verify roundtrip: OK");
exit(0);
