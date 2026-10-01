<?php
$pages   = (int) ($pages ?? 1);
$pageNow = max(1, (int) ($pageNow ?? 1));
$base    = $baseUrl ?? site_url('/');
$anchor  = $anchor ?? '';
if ($pages > 1):
    $from = max(1, $pageNow - 3);
    $to   = min($pages, $pageNow + 3);
    $href = static function (int $p) use ($base, $anchor): string {
        $url = $base . (str_contains($base, '?') ? '&' : '?') . 'page=' . $p;
        return $url . $anchor;
    };
?>
<nav class="mt-4 d-flex justify-content-center" aria-label="Product pages">
    <ul class="pagination flex-wrap mb-0">
        <?php if ($pageNow > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= esc($href($pageNow - 1)) ?>">Prev</a></li>
        <?php endif; ?>
        <?php if ($from > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= esc($href(1)) ?>">1</a></li>
            <?php if ($from > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $from; $i <= $to; $i++): ?>
            <li class="page-item <?= $i === $pageNow ? 'active' : '' ?>">
                <a class="page-link" href="<?= esc($href($i)) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($to < $pages): ?>
            <?php if ($to < $pages - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= esc($href($pages)) ?>"><?= $pages ?></a></li>
        <?php endif; ?>
        <?php if ($pageNow < $pages): ?>
            <li class="page-item"><a class="page-link" href="<?= esc($href($pageNow + 1)) ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
