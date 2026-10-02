<?php

namespace App\Controllers\Customer;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\ProductQuestionModel;
use App\Models\ReviewModel;
use App\Models\ShippingZoneModel;
use App\Models\StoreFollowModel;
use App\Models\WishlistModel;
use App\Services\Catalog\PricingService;

class CatalogController extends BaseController
{
    protected ProductModel $productModel;
    protected CategoryModel $categoryModel;
    protected ReviewModel $reviewModel;
    protected PricingService $pricingService;

    public function __construct()
    {
        $this->productModel   = new ProductModel();
        $this->categoryModel  = new CategoryModel();
        $this->reviewModel    = new ReviewModel();
        $this->pricingService = new PricingService();
    }

    public function index()
    {
        $categorySlug = $this->request->getGet('category');
        $categoryId   = $this->request->getGet('category_id');
        $categoryIds  = [];
        try {
            $categoryIds = $this->categoryModel->catalogIds($categorySlug, $categoryId);
        } catch (\Throwable $e) {
            $categoryIds = [];
        }
        if ($categoryIds !== []) {
            $categoryId = $categoryIds[0];
        }

        $filters = [
            'category_id'   => $categoryId,
            'category_ids'  => $categoryIds,
            'category_slug' => $categorySlug,
            'search'        => $this->request->getGet('q') ?? $this->request->getGet('search'),
            'brand'         => $this->request->getGet('brand'),
            'mall'          => $this->request->getGet('mall'),
            'sponsored'     => $this->request->getGet('sponsored'),
            'min_price'     => $this->request->getGet('min_price'),
            'max_price'     => $this->request->getGet('max_price'),
            'sort'          => $this->request->getGet('sort') ?? 'latest',
            'limit'         => 24,
            'page'          => max(1, (int) ($this->request->getGet('page') ?? 1)),
        ];

        $total      = $this->productModel->countCatalog($filters);
        $pageCount  = max(1, (int) ceil($total / (int) $filters['limit']));
        if ($filters['page'] > $pageCount) {
            $filters['page'] = $pageCount;
        }

        $products   = $this->productModel->getCatalog($filters);
        $searchHint = null;
        if ($products === [] && ! empty($filters['search']) && strlen((string) $filters['search']) > 3) {
            $short = substr((string) $filters['search'], 0, 4);
            $alt = $this->productModel->getCatalog(array_merge($filters, ['search' => $short]));
            if ($alt !== []) {
                $products = $alt;
                $searchHint = $short;
            }
        }
        $flashMap = [];
        try {
            $flashMap = $this->pricingService->getFlashPriceMap();
        } catch (\Throwable $e) {
            $flashMap = [];
        }
        foreach ($products as &$p) {
            if (isset($flashMap[(int) $p['id']])) {
                $p['flash_price'] = $flashMap[(int) $p['id']];
            }
        }
        unset($p);

        $categories = [];
        try {
            $categories = $this->categoryModel->getTree();
        } catch (\Throwable $e) {
            $categories = [];
        }
        $brands = [];
        try {
            $brands = $this->productModel->select('brand')
                ->where('status', 'active')
                ->where('brand !=', '')
                ->groupBy('brand')
                ->orderBy('brand', 'ASC')
                ->findAll();
        } catch (\Throwable $e) {
            $brands = [];
        }

        return view('customer/catalog', [
            'title'      => 'Browse Products — Solqam Market Place',
            'products'   => $products,
            'categories' => $categories,
            'brands'     => $brands,
            'filters'    => $filters,
            'searchHint' => $searchHint ?? null,
            'catalogTotal' => $total,
            'categoryPath' => $categoryId ? $this->categoryModel->breadcrumb((int) $categoryId) : [],
            'catalogPages' => $pageCount,
            'recent'     => $this->recentProducts(),
        ]);
    }

    public function detail($id)
    {
        $product = $this->productModel->getDetailedProduct((int) $id);
        if (!$product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Product not found');
        }

        $reviews = $this->reviewModel->getProductReviews((int) $id);
        $ratingStats = $this->reviewModel->getProductRatingStats((int) $id);
        $ratingBreakdown = $this->reviewModel->getRatingBreakdown((int) $id);
        $questions = (new ProductQuestionModel())->forProduct((int) $id);
        $flashMap = $this->pricingService->getFlashPriceMap();
        $flashPrice = $flashMap[(int) $id] ?? null;

        $canReview = false;
        $session = session();
        $deliveredOrderId = null;
        $inWishlist = false;
        $isFollowing = false;
        if ($session->get('user.id')) {
            $userId = (int) $session->get('user.id');
            $inWishlist = (new WishlistModel())->isSaved($userId, (int) $id);
            $isFollowing = (new StoreFollowModel())->isFollowing($userId, (int) $product['seller_id']);
            $db = \Config\Database::connect();
            $deliveredOrder = $db->table('order_items')
                ->select('order_items.order_id')
                ->join('orders', 'orders.id = order_items.order_id')
                ->where('order_items.product_id', $id)
                ->where('orders.user_id', $userId)
                ->where('orders.status', 'delivered')
                ->get()
                ->getRowArray();

            if ($deliveredOrder) {
                $deliveredOrderId = $deliveredOrder['order_id'];
                $alreadyReviewed = $this->reviewModel
                    ->where('product_id', $id)
                    ->where('user_id', $userId)
                    ->where('order_id', $deliveredOrderId)
                    ->first();
                if (!$alreadyReviewed) {
                    $canReview = true;
                }
            }
        }

        $relatedIds = $this->categoryModel->catalogIds(null, $product['category_id'] ?? 0);
        $relatedProducts = $this->productModel->getCatalog([
            'category_ids' => $relatedIds !== [] ? $relatedIds : null,
            'category_id'  => $product['category_id'] ?? null,
            'limit'        => 8,
        ]);
        $relatedProducts = array_values(array_filter($relatedProducts, static fn ($row) => (int) $row['id'] !== (int) $id));
        $relatedProducts = array_slice($relatedProducts, 0, 4);

        $followerCount = (new StoreFollowModel())->followerCount((int) $product['seller_id']);
        $shippingZones = (new ShippingZoneModel())->orderBy('city', 'ASC')->findAll();

        $colorFamily = [];
        $sizeOptions = [];
        foreach ($product['variants'] ?? [] as $variant) {
            $color = trim((string) ($variant['color'] ?? ''));
            $size  = trim((string) ($variant['size'] ?? ''));
            if ($color !== '') {
                $colorFamily[$color] = $color;
            }
            if ($size !== '') {
                $sizeOptions[$size] = $size;
            }
        }

        $specs = $this->parseSpecifications($product['specifications'] ?? '');
        if ($specs === []) {
            $specs = array_filter([
                'Brand'    => trim((string) ($product['brand'] ?? '')),
                'SKU'      => $product['sku'] ?? '',
                'Category' => $product['category_name'] ?? '',
                'Stock'    => isset($product['stock']) ? (string) $product['stock'] : '',
            ], static fn ($value) => $value !== '' && $value !== null);
        }

        $highlights = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n/', (string) ($product['highlights'] ?? '')) ?: [])));

        $seen = session()->get('recently_viewed') ?? [];
        array_unshift($seen, (int) $id);
        $seen = array_values(array_unique(array_map('intval', $seen)));
        session()->set('recently_viewed', array_slice($seen, 0, 12));

        return view('customer/product_detail', [
            'title'            => $product['name'] . ' — Solqam Market Place',
            'product'          => $product,
            'reviews'          => $reviews,
            'ratingStats'      => $ratingStats,
            'ratingBreakdown'  => $ratingBreakdown,
            'canReview'        => $canReview,
            'deliveredOrderId' => $deliveredOrderId,
            'relatedProducts'  => $relatedProducts,
            'categoryPath'     => $this->categoryModel->breadcrumb((int) ($product['category_id'] ?? 0)),
            'questions'        => $questions,
            'flashPrice'       => $flashPrice,
            'inWishlist'       => $inWishlist,
            'isFollowing'      => $isFollowing,
            'followerCount'    => $followerCount,
            'shippingZones'    => $shippingZones,
            'colorFamily'      => array_values($colorFamily),
            'sizeOptions'      => array_values($sizeOptions),
            'specs'            => $specs,
            'highlights'       => $highlights,
        ]);
    }

    public function askQuestion($id)
    {
        $userId = (int) session()->get('user.id');
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Login to ask a question.');
        }
        $question = trim((string) $this->request->getPost('question'));
        if ($question === '') {
            return redirect()->back()->with('error', 'Please write a question.');
        }

        (new ProductQuestionModel())->insert([
            'product_id' => (int) $id,
            'user_id'    => $userId,
            'question'   => $question,
        ]);

        return redirect()->to('/product/' . $id . '#qa-pane')->with('success', 'Question submitted. The seller will reply soon.');
    }

    public function compareToggle($id)
    {
        $ids = session()->get('compare_ids') ?? [];
        $id = (int) $id;
        if (in_array($id, $ids, true)) {
            $ids = array_values(array_diff($ids, [$id]));
        } else {
            $ids[] = $id;
            $ids = array_slice(array_values(array_unique($ids)), 0, 4);
        }
        session()->set('compare_ids', $ids);
        return redirect()->back()->with('success', 'Compare list updated.');
    }

    public function compare()
    {
        $ids = session()->get('compare_ids') ?? [];
        $products = [];
        foreach ($ids as $id) {
            $p = $this->productModel->getDetailedProduct((int) $id);
            if ($p) {
                $products[] = $p;
            }
        }
        return view('customer/compare', [
            'title'    => 'Compare — Solqam',
            'products' => $products,
        ]);
    }

    protected function recentProducts(): array
    {
        $ids = session()->get('recently_viewed') ?? [];
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach (array_slice($ids, 0, 6) as $id) {
            $p = $this->productModel->select('id, name, price, slug')->find((int) $id);
            if ($p) {
                $out[] = $p;
            }
        }
        return $out;
    }

    protected function parseSpecifications(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $json = json_decode($raw, true);
        if (is_array($json) && $json !== []) {
            return $json;
        }

        $out = [];
        foreach (preg_split('/\r\n|\n/', $raw) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key !== '' && $value !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
