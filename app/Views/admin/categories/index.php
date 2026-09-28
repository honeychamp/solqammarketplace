<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Marketplace Categories</h4>
        <p class="text-secondary small mb-0">Organize and manage catalog taxonomies</p>
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
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Parent</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>#<?= $cat['id'] ?></td>
                        <td class="fw-bold text-dark"><?= esc($cat['name']) ?></td>
                        <td><code><?= esc($cat['slug']) ?></code></td>
                        <td class="small text-secondary"><?= esc($cat['description']) ?></td>
                        <td class="small"><?= !empty($cat['parent_id']) ? '#' . $cat['parent_id'] : 'Top-level' ?></td>
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

<!-- Modal for New Category -->
<div class="modal fade" id="newCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Create New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= site_url('admin/categories/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Health &amp; Beauty" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Parent Category (optional)</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Top-level</option>
                            <?php foreach ($categories as $parent): ?>
                                <?php if (empty($parent['parent_id'])): ?>
                                    <option value="<?= $parent['id'] ?>"><?= esc($parent['name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
