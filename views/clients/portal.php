<?php
require_once dirname(__DIR__) . '/components/ui.php';

$title = $title ?? 'Client Portal';
$activeNav = 'contracts';
$headerMeta = 'client portal';
$pageTitle = 'Client Portal';
$pageHeading = $client['company_name'] ?? $client['name'] ?? 'Client Portal';
$pageEyebrow = 'secure contract access';
$pageLead = 'Review contracts awaiting your signature and open final read-only copies.';

ob_start();
?>
<section class="surface">
    <div class="responsive-table">
        <table class="contracts-table">
            <thead>
                <tr>
                    <th>Contract</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (($contracts ?? []) as $contract): ?>
                <?php $state = strtoupper($contract['signing_state'] ?? 'DRAFT'); ?>
                <tr>
                    <td><strong><?= ui_e($contract['title'] ?? 'Untitled Contract') ?></strong></td>
                    <td><span class="status-pill <?= ui_status_class($state) ?>"><?= ui_e(ui_status_label($state)) ?></span></td>
                    <td><?= ui_e($contract['updated_at'] ?? $contract['created_at'] ?? '') ?></td>
                    <td>
                        <?php if ($state === 'AWAITING_CLIENT'): ?>
                            <a class="row-action primary" href="<?= BASE_URL ?>/contracts/sign/<?= (int) $contract['id'] ?>">Sign</a>
                        <?php else: ?>
                            <a class="row-action" href="<?= BASE_URL ?>/contracts/view/<?= (int) $contract['id'] ?>">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($contracts)): ?>
                <tr><td colspan="4">No contracts are available for this client yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/app.php';
