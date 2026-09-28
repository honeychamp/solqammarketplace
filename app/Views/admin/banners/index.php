<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<h4 class="fw-bold mb-4">Homepage Banners</h4>
<div class="card-custom p-4 mb-4">
    <form action="<?= site_url('admin/banners/store') ?>" method="POST" class="row g-2">
        <?= csrf_field() ?>
        <div class="col-md-4"><input name="title" class="form-control" placeholder="Title" required></div>
        <div class="col-md-4"><input name="subtitle" class="form-control" placeholder="Subtitle"></div>
        <div class="col-md-4"><input name="link_url" class="form-control" placeholder="/shop"></div>
        <div class="col-md-4"><input name="image_path" class="form-control" placeholder="Image URL"></div>
        <div class="col-md-2">
            <select name="placement" class="form-select">
                <option value="hero">Hero</option>
                <option value="side">Side</option>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div>
    </form>
</div>
<div class="card-custom p-4">
    <table class="table">
        <thead><tr><th>Title</th><th>Placement</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
            <tr>
                <td><?= esc($b['title']) ?></td>
                <td><?= esc($b['placement']) ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-danger" href="<?= site_url('admin/banners/delete/' . $b['id']) ?>">Delete</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
