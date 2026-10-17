<?php
if (empty($pager) || ! is_object($pager)) {
    return;
}
try {
    $pageCount = method_exists($pager, 'getPageCount') ? (int) $pager->getPageCount() : 0;
} catch (\Throwable $e) {
    $pageCount = 0;
}
if ($pageCount < 2) {
    return;
}
$keep  = $keep ?? [];
$links = $keep !== [] && $keep !== null
    ? $pager->only((array) $keep)->links()
    : $pager->links();
$current = method_exists($pager, 'getCurrentPage') ? (int) $pager->getCurrentPage() : 1;
?>
<nav class="hub-pager d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4" aria-label="Pagination">
    <div class="small text-muted">Page <?= $current ?> of <?= $pageCount ?></div>
    <?= $links ?>
</nav>
