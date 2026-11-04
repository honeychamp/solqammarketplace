<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?= view('shared/inventory', get_defined_vars()) ?>
<?= $this->endSection() ?>
