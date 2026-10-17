<?php
/**
 * @var \CodeIgniter\Pager\PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<?php
$canPrev = $pager->hasPreviousPage();
$canNext = $pager->hasNextPage();
$canFirst = $canPrev || $pager->hasPrevious();
$canLast  = $canNext || $pager->hasNext();
?>
<ul class="pagination hub-pagination mb-0 flex-wrap">
    <li class="page-item <?= $canFirst ? '' : 'disabled' ?>">
        <?php if ($canFirst): ?>
            <a class="page-link" href="<?= $pager->getFirst() ?>">First</a>
        <?php else: ?>
            <span class="page-link">First</span>
        <?php endif; ?>
    </li>
    <li class="page-item <?= $canPrev ? '' : 'disabled' ?>">
        <?php if ($canPrev): ?>
            <a class="page-link" href="<?= $pager->getPreviousPage() ?>">Prev</a>
        <?php else: ?>
            <span class="page-link">Prev</span>
        <?php endif; ?>
    </li>
    <?php foreach ($pager->links() as $link): ?>
        <li class="page-item <?= ! empty($link['active']) ? 'active' : '' ?>">
            <a class="page-link" href="<?= $link['uri'] ?>"><?= esc($link['title']) ?></a>
        </li>
    <?php endforeach; ?>
    <li class="page-item <?= $canNext ? '' : 'disabled' ?>">
        <?php if ($canNext): ?>
            <a class="page-link" href="<?= $pager->getNextPage() ?>">Next</a>
        <?php else: ?>
            <span class="page-link">Next</span>
        <?php endif; ?>
    </li>
    <li class="page-item <?= $canLast ? '' : 'disabled' ?>">
        <?php if ($canLast): ?>
            <a class="page-link" href="<?= $pager->getLast() ?>">Last</a>
        <?php else: ?>
            <span class="page-link">Last</span>
        <?php endif; ?>
    </li>
</ul>
