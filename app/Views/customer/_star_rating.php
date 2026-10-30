<?php
$avg   = (float) ($product['avg_rating'] ?? 0);
$count = (int) ($product['review_count'] ?? 0);
$fill  = $count > 0 ? (int) round($avg) : 0;
?>
<div class="star-rating">
    <?php for ($i = 1; $i <= 5; $i++): ?>
        <i class="bi bi-star<?= $i <= $fill ? '-fill' : '' ?>"></i>
    <?php endfor; ?>
    <span class="review-count">(<?= $count ?>)</span>
</div>
