<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-2">Homepage Banners</h4>
<p class="text-muted mb-4">The photo is the background. Title, subtitle, badge, buttons and the three stats cards stay on top — same layout as the default hero.</p>
<div class="card-custom p-4 mb-4">
    <form action="<?= site_url('admin/banners/store') ?>" method="POST" enctype="multipart/form-data" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Headline</label>
            <input name="title" class="form-control" placeholder="Shop genuine brands. Earn wallet cashback." required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Subtitle</label>
            <input name="subtitle" class="form-control" placeholder="Electronics, fashion, groceries…">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Badge</label>
            <input name="badge_text" class="form-control" placeholder="Solqam Marketplace">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Button text</label>
            <input name="button_text" class="form-control" placeholder="Shop now">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Button link</label>
            <input name="link_url" class="form-control" placeholder="<?= site_url('shop') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Background image (upload)</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">or image URL</label>
            <input name="image_path" class="form-control" placeholder="https://…">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Placement</label>
            <select name="placement" class="form-select">
                <option value="hero">Hero (main banner)</option>
                <option value="side">Side card</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Starts</label>
            <input type="datetime-local" name="starts_at" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Ends</label>
            <input type="datetime-local" name="ends_at" class="form-control">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100">Save banner</button>
        </div>
    </form>
</div>
<div class="card-custom p-4">
    <table class="table">
        <thead><tr><th>Title</th><th>Placement</th><th>Schedule</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
            <?php
            $toLocal = static fn ($v) => $v ? str_replace(' ', 'T', substr((string) $v, 0, 16)) : '';
            ?>
            <tr>
                <td colspan="4" class="p-3">
                    <form action="<?= site_url('admin/banners/update/' . $b['id']) ?>" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-12 d-flex align-items-center gap-2">
                            <?php if (!empty($b['image_path'])): ?>
                                <img src="<?= esc(preg_match('#^https?://#i', $b['image_path']) ? $b['image_path'] : base_url(ltrim($b['image_path'], '/'))) ?>" alt="" style="height:36px;width:64px;object-fit:cover;border-radius:6px;">
                            <?php endif; ?>
                            <strong><?= esc($b['title']) ?></strong>
                            <span class="badge bg-light text-dark"><?= esc($b['placement']) ?></span>
                        </div>
                        <div class="col-md-4"><input name="title" class="form-control form-control-sm" value="<?= esc($b['title']) ?>" required></div>
                        <div class="col-md-4"><input name="subtitle" class="form-control form-control-sm" value="<?= esc($b['subtitle'] ?? '') ?>"></div>
                        <div class="col-md-2"><input name="badge_text" class="form-control form-control-sm" value="<?= esc($b['badge_text'] ?? '') ?>" placeholder="Badge"></div>
                        <div class="col-md-2"><input name="button_text" class="form-control form-control-sm" value="<?= esc($b['button_text'] ?? '') ?>" placeholder="Button"></div>
                        <div class="col-md-4"><input name="link_url" class="form-control form-control-sm" value="<?= esc($b['link_url'] ?? '') ?>" placeholder="Link"></div>
                        <div class="col-md-3"><input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif"></div>
                        <div class="col-md-3"><input name="image_path" class="form-control form-control-sm" value="<?= esc($b['image_path'] ?? '') ?>" placeholder="Image URL"></div>
                        <div class="col-md-2">
                            <select name="placement" class="form-select form-select-sm">
                                <option value="hero" <?= ($b['placement'] ?? '') === 'hero' ? 'selected' : '' ?>>Hero</option>
                                <option value="side" <?= ($b['placement'] ?? '') === 'side' ? 'selected' : '' ?>>Side</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="is_active" class="form-select form-select-sm">
                                <option value="1" <?= !empty($b['is_active']) ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= empty($b['is_active']) ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3"><input type="datetime-local" name="starts_at" class="form-control form-control-sm" value="<?= esc($toLocal($b['starts_at'] ?? '')) ?>"></div>
                        <div class="col-md-3"><input type="datetime-local" name="ends_at" class="form-control form-control-sm" value="<?= esc($toLocal($b['ends_at'] ?? '')) ?>"></div>
                        <div class="col-md-6 d-flex gap-2">
                            <button class="btn btn-sm btn-primary">Save changes</button>
                            <a class="btn btn-sm btn-outline-danger" href="<?= site_url('admin/banners/delete/' . $b['id']) ?>" onclick="return confirm('Delete this banner?');">Delete</a>
                        </div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?= view('shared/_pager', ['pager' => $pager ?? null]) ?>
</div>
<?= $this->endSection() ?>
