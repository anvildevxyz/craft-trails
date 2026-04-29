<?php

declare(strict_types=1);

namespace anvildev\trails\services;

use anvildev\trails\anchors\AnchorBackendInterface;
use anvildev\trails\anchors\Rfc3161AnchorBackend;
use anvildev\trails\anchors\S3AnchorBackend;
use anvildev\trails\records\AnchorRecord;
use anvildev\trails\records\MerkleRootRecord;
use anvildev\trails\Trails;
use Craft;
use craft\base\Component;
use craft\helpers\App;

class AnchorService extends Component
{
    /**
     * Creates and returns the configured anchor backend, or null if none is configured.
     */
    public function getBackend(): ?AnchorBackendInterface
    {
        $settings = Trails::getInstance()->getSettings();
        return $this->getBackendByType($settings->anchorType ?? null);
    }

    /**
     * Build a backend for the given anchor type, ignoring the current Settings::anchorType.
     * Used by verifyAll() so legacy anchors keep verifying after the active backend changes.
     */
    public function getBackendByType(?string $anchorType): ?AnchorBackendInterface
    {
        $settings = Trails::getInstance()->getSettings();

        if ($anchorType === 's3') {
            $accessKeyId = App::parseEnv($settings->s3AccessKeyId ?? '');
            $secretAccessKey = App::parseEnv($settings->s3SecretAccessKey ?? '');

            $endpoint = (string) (App::parseEnv($settings->s3Endpoint ?? '') ?? '');

            return new S3AnchorBackend(
                bucket: (string) (App::parseEnv($settings->s3Bucket ?? '') ?? ''),
                region: (string) (App::parseEnv($settings->s3Region ?? '') ?? ''),
                accessKeyId: $accessKeyId ?: null,
                secretAccessKey: $secretAccessKey ?: null,
                retentionYears: 7,
                endpoint: $endpoint !== '' ? $endpoint : null,
                usePathStyle: (bool) ($settings->s3UsePathStyle ?? false),
            );
        }

        if ($anchorType === 'rfc3161') {
            return new Rfc3161AnchorBackend(
                tsaUrl: (string) (App::parseEnv($settings->tsaUrl ?? '') ?? ''),
                caBundlePath: (string) (App::parseEnv($settings->tsaTrustedCaBundle ?? '') ?? ''),
                caBundlePem: (string) (App::parseEnv($settings->tsaCaBundlePem ?? '') ?? ''),
            );
        }

        return null;
    }

    /**
     * Anchors a Merkle root via the configured backend and persists the result.
     *
     * @return AnchorRecord|null The saved anchor record, or null on failure.
     */
    public function anchor(MerkleRootRecord $root): ?AnchorRecord
    {
        try {
            $backend = $this->getBackend();

            if ($backend === null) {
                return null;
            }

            $result = $backend->anchor($root);

            $settings = Trails::getInstance()->getSettings();

            $anchor = new AnchorRecord();
            $anchor->merkleRootId = (int) $root->id;
            $anchor->anchorType = $settings->anchorType ?? '';
            $anchor->anchorRef = $result['anchorRef'];
            $anchor->anchorProof = $result['anchorProof'];
            $anchor->verified = true;
            $anchor->dateAnchored = date('Y-m-d H:i:s');

            if (!$anchor->save()) {
                Craft::error(
                    'AnchorService: failed to save AnchorRecord for MerkleRootRecord #' . $root->id,
                    __METHOD__
                );

                return null;
            }

            return $anchor;
        } catch (\Throwable $e) {
            Craft::error(
                'AnchorService::anchor() failed: ' . $e->getMessage(),
                __METHOD__
            );

            return null;
        }
    }

    /**
     * Iterates all AnchorRecords, re-verifies each via the backend, and updates the verified flag.
     *
     * @return array{verified: int, failed: int, failedIds: int[]}
     */
    public function verifyAll(): array
    {
        $verified = 0;
        $failed = 0;
        $failedIds = [];

        /** @var AnchorRecord[] $anchors */
        $anchors = AnchorRecord::find()->orderBy(['id' => SORT_ASC])->all();

        if (empty($anchors)) {
            return [
                'verified' => $verified,
                'failed' => $failed,
                'failedIds' => $failedIds,
            ];
        }

        foreach ($anchors as $anchor) {
            /** @var MerkleRootRecord|null $root */
            $root = MerkleRootRecord::findOne((int) $anchor->merkleRootId);

            if ($root === null) {
                $anchor->verified = false;
                $anchor->save(false);
                $failed++;
                $failedIds[] = (int) $anchor->id;
                continue;
            }

            $backend = $this->getBackendByType((string) $anchor->anchorType);
            if ($backend === null) {
                Craft::warning(
                    "AnchorService::verifyAll(): no backend configured for anchorType '{$anchor->anchorType}' (anchor #{$anchor->id})",
                    __METHOD__
                );
                $anchor->verified = false;
                $anchor->save(false);
                $failed++;
                $failedIds[] = (int) $anchor->id;
                continue;
            }

            try {
                $isVerified = $backend->verify(
                    (string) $anchor->anchorRef,
                    (string) $anchor->anchorProof,
                    (string) $root->rootHash,
                );
            } catch (\Throwable $e) {
                Craft::error(
                    'AnchorService::verifyAll() failed for AnchorRecord #' . $anchor->id . ': ' . $e->getMessage(),
                    __METHOD__
                );
                $isVerified = false;
            }

            $anchor->verified = $isVerified;
            $anchor->save(false);

            if ($isVerified) {
                $verified++;
            } else {
                $failed++;
                $failedIds[] = (int) $anchor->id;
            }
        }

        return [
            'verified' => $verified,
            'failed' => $failed,
            'failedIds' => $failedIds,
        ];
    }
}
