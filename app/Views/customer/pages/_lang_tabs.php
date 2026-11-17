<?php $p = $tabPrefix ?? 'legal'; ?>
<ul class="nav nav-pills nav-fill mb-4 p-1 rounded-4 gap-1" style="background:#F1F5F9;" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill fw-semibold py-2" id="<?= esc($p, 'attr') ?>-en-tab" data-bs-toggle="tab" data-bs-target="#<?= esc($p, 'attr') ?>-en" type="button" role="tab">English</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill fw-semibold py-2" id="<?= esc($p, 'attr') ?>-ur-tab" data-bs-toggle="tab" data-bs-target="#<?= esc($p, 'attr') ?>-ur" type="button" role="tab">اردو</button>
    </li>
</ul>
