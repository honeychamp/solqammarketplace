<?php if (! empty($pager) && $pager->getPageCount() > 1): ?>
    <div class="d-flex justify-content-center mt-3">
        <?= $pager->links() ?>
    </div>
<?php endif; ?>
