<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Marketplace Categories</h4>
        <p class="text-secondary small mb-0">Daraz-style tree: up to 4 levels (e.g. Electronics → Mobiles → Smartphones → Samsung). Seller commission can inherit from the parent. Admin-store products stay at 0%.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newCatModal">
        <i class="bi bi-plus-lg me-1"></i> Add Category
    </button>
</div>

<div class="card-custom p-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small">
                <tr>
                    <th>Image</th>
                    <th>Category path</th>
                    <th>Level</th>
                    <th>Commission %</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>
                            <?php if (!empty($cat['image'])): ?>
                                <img src="<?= esc(media_url($cat['image'])) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:10px;">
                            <?php else: ?>
                                <div class="bg-light text-muted d-flex align-items-center justify-content-center" style="width:48px;height:48px;border-radius:10px;"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark" style="padding-left: <?= max(0, ((int) ($cat['depth'] ?? 1) - 1) * 14) ?>px;">
                                <?= esc($cat['path_label'] ?? $cat['name']) ?>
                            </div>
                            <code class="small"><?= esc($cat['slug']) ?></code>
                        </td>
                        <td class="small">L<?= (int) ($cat['depth'] ?? 1) ?> · <?= esc($cat['parent_name'] ?? 'Top-level') ?></td>
                        <td>
                            <form action="<?= site_url('admin/categories/update/' . $cat['id']) ?>" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2" style="min-width:180px;">
                                <?= csrf_field() ?>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="50" name="commission_percent" class="form-control" value="<?= esc($cat['commission_percent'] ?? '') ?>" placeholder="<?= !empty($cat['parent_id']) ? 'Inherit' : '10' ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="small text-muted">Applies <?= number_format((float) ($cat['effective_commission'] ?? 10), 1) ?>%</div>
                                <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/gif">
                                <button class="btn btn-sm btn-primary">Save</button>
                            </form>
                        </td>
                        <td>
                            <span class="badge bg-<?= ($cat['is_active']) ? 'success' : 'secondary' ?>">
                                <?= ($cat['is_active']) ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= site_url('admin/categories/delete/' . $cat['id']) ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this category?');">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="newCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Create New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= site_url('admin/categories/store') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Smartphones" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Parent (leave empty for L1)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Top-level (Level 1)</option>
                            <?php foreach (($parentOptions ?? []) as $parent): ?>
                                <option value="<?= $parent['id'] ?>"><?= esc($parent['label']) ?> (L<?= (int) $parent['depth'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Pick a parent up to Level 3 so the new row can be Level 4 (Samsung under Smartphones).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Seller commission %</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="50" name="commission_percent" class="form-control" placeholder="Leave empty on child to inherit parent">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">0–50%. Admin’s own listings stay at 0%.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category image</label>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Summary of items in this category..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
