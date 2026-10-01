<?php
$nodes   = $nodes ?? [];
$filters = $filters ?? [];
$depth   = (int) ($depth ?? 0);
$searchQ = !empty($filters['search']) ? '&q=' . urlencode($filters['search']) : '';
foreach ($nodes as $node):
    $isSelected = (($filters['category_id'] ?? '') == $node['id'] || ($filters['category_slug'] ?? '') == ($node['slug'] ?? ''));
    $pad = $depth * 12;
?>
    <a href="<?= site_url('shop?category=' . esc($node['slug']) . $searchQ) ?>"
       class="text-decoration-none small py-1 pe-2 rounded-2 d-flex justify-content-between align-items-center <?= $isSelected ? 'bg-solqam text-white fw-bold' : ($depth ? 'text-muted' : 'text-dark hover-bg') ?>"
       style="padding-left: <?= 8 + $pad ?>px;">
        <span><?= $depth ? str_repeat('· ', min($depth, 3)) : '' ?><?= esc($node['name']) ?></span>
        <?php if ($isSelected): ?><i class="bi bi-check2"></i><?php endif; ?>
    </a>
    <?php if (!empty($node['children'])): ?>
        <?= view('shared/category_filter_nodes', [
            'nodes'   => $node['children'],
            'filters' => $filters,
            'depth'   => $depth + 1,
        ]) ?>
    <?php endif; ?>
<?php endforeach; ?>
