<?php

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Services\Commission\SellerDueService;
use App\Services\Platform\SettingService;

class DueController extends BaseController
{
    public function index()
    {
        $sellerId = (int) session()->get('user.id');
        $dues     = new SellerDueService();

        return view('seller/dues/index', [
            'title'      => 'Pay Solqam — Seller Hub',
            'accounts'   => SettingService::collectionAccounts(),
            'hasPayTo'   => SettingService::hasCollectionAccounts(),
            'dueSummary' => $dues->summary($sellerId),
            'dueLines'   => $dues->linesForSeller($sellerId),
        ]);
    }

    public function submitProof($id)
    {
        $sellerId = (int) session()->get('user.id');
        $note     = trim((string) $this->request->getPost('proof_note'));
        try {
            $path = $this->storeProofFile('due' . (int) $id . '_');
            (new SellerDueService())->submitProof($sellerId, (int) $id, $path, $note);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to('/seller/dues')->with('success', 'Screenshot submitted. Status is Processing until Solqam confirms the payment.');
    }

    public function submitTotalProof()
    {
        $sellerId = (int) session()->get('user.id');
        $note     = trim((string) $this->request->getPost('proof_note'));
        try {
            $path = $this->storeProofFile('dueall' . $sellerId . '_');
            $n    = (new SellerDueService())->submitProofForOpenDues($sellerId, $path, $note);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->to('/seller/dues')->with('success', 'Total payment screenshot submitted. ' . $n . ' dues are Processing until admin confirms.');
    }

    protected function storeProofFile(string $prefix): string
    {
        $img = $this->request->getFile('proof_image');
        if (! $img || ! $img->isValid() || $img->hasMoved()) {
            throw new \RuntimeException('Payment screenshot is required.');
        }
        if ($img->getSize() > 4 * 1024 * 1024) {
            throw new \RuntimeException('Screenshot must be under 4 MB.');
        }
        $ext = strtolower((string) $img->getExtension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new \RuntimeException('Use a JPG, PNG or WEBP screenshot.');
        }
        $dir = FCPATH . 'uploads/dues';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $prefix . $img->getRandomName();
        $img->move($dir, $name);

        return 'uploads/dues/' . $name;
    }
}
