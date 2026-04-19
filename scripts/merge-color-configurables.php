#!/usr/bin/env php
<?php
/**
 * Merge color-split configurables into unified products.
 *
 * Problem: Some products have one configurable per color (e.g. 00330030-GR, 00330030-NE)
 * instead of a single configurable with color + size. This means that on the product page
 * for GR, the user cannot switch to NE, even though both are the same model.
 *
 * Solution: For each group of configurables sharing the same base SKU, this script links
 * ALL simples from every color variant as children of every configurable in the group.
 * This way each product page shows all available colors.
 *
 * Usage:
 *   cd /var/www/html/armario33
 *   php scripts/merge-color-configurables.php                  # dry-run (default)
 *   php scripts/merge-color-configurables.php --execute        # actually insert links
 *   php scripts/merge-color-configurables.php --sku=00330030   # process a single base SKU
 *
 * After running with --execute:
 *   php bin/magento indexer:reindex
 *   php bin/magento cache:flush
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();

$dryRun = !in_array('--execute', $argv);
$filterSku = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--sku=') === 0) {
        $filterSku = substr($arg, 6);
    }
}

echo $dryRun ? "=== DRY RUN (use --execute to apply) ===\n\n" : "=== EXECUTING ===\n\n";

// Find groups of configurables that share a base SKU (part before the last hyphen-color suffix)
$sql = "SELECT entity_id, sku FROM catalog_product_entity WHERE type_id = 'configurable' ORDER BY sku";
$configurables = $connection->fetchAll($sql);

$groups = [];
foreach ($configurables as $row) {
    $lastDash = strrpos($row['sku'], '-');
    if ($lastDash === false) continue;
    $baseSku = substr($row['sku'], 0, $lastDash);
    $groups[$baseSku][] = $row;
}

$totalLinksAdded = 0;

foreach ($groups as $baseSku => $members) {
    if (count($members) < 2) continue;
    if ($filterSku !== null && $baseSku !== $filterSku) continue;

    $memberIds = array_column($members, 'entity_id');
    $memberSkus = array_column($members, 'sku');
    echo "Group: {$baseSku} (" . implode(', ', $memberSkus) . ")\n";

    // Get ALL simples currently linked to ANY configurable in this group
    $allChildIds = $connection->fetchCol(
        "SELECT DISTINCT product_id FROM catalog_product_super_link WHERE parent_id IN (" . implode(',', $memberIds) . ")"
    );

    if (empty($allChildIds)) {
        echo "  No children found, skipping.\n\n";
        continue;
    }

    echo "  Total unique simples across group: " . count($allChildIds) . "\n";

    // For each configurable in the group, add missing children
    foreach ($members as $config) {
        $existingChildIds = $connection->fetchCol(
            "SELECT product_id FROM catalog_product_super_link WHERE parent_id = ?",
            [$config['entity_id']]
        );

        $missingIds = array_diff($allChildIds, $existingChildIds);
        if (empty($missingIds)) {
            echo "  {$config['sku']}: already has all " . count($allChildIds) . " children\n";
            continue;
        }

        echo "  {$config['sku']}: adding " . count($missingIds) . " missing children\n";
        $totalLinksAdded += count($missingIds);

        if (!$dryRun) {
            foreach ($missingIds as $childId) {
                $connection->insertOnDuplicate(
                    $resource->getTableName('catalog_product_super_link'),
                    ['product_id' => $childId, 'parent_id' => $config['entity_id']],
                    ['product_id']
                );
                $connection->insertOnDuplicate(
                    $resource->getTableName('catalog_product_relation'),
                    ['parent_id' => $config['entity_id'], 'child_id' => $childId],
                    ['child_id']
                );
            }
        }
    }
    echo "\n";
}

echo "Total links " . ($dryRun ? "to add" : "added") . ": {$totalLinksAdded}\n";

if (!$dryRun && $totalLinksAdded > 0) {
    echo "\nDone. Now run:\n";
    echo "  php bin/magento indexer:reindex\n";
    echo "  php bin/magento cache:flush\n";
}
