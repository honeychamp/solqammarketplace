<?php

namespace App\Commands;

use App\Services\Catalog\StarterReviewService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Top every active product up to 11–12 reviews (keeps real customer reviews).
 * Includes Whole Trades shops.
 *
 *   php spark reviews:seed
 */
class SeedStarterReviews extends BaseCommand
{
    protected $group       = 'Solqam';
    protected $name        = 'reviews:seed';
    protected $description = 'Top products up to 11–12 reviews, including Whole Trades.';

    public function run(array $params)
    {
        try {
            $result = StarterReviewService::ensure(true);
        } catch (Throwable $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write(
            'Starter reviews: ' . $result['products'] . ' products topped to 11–12, ' . $result['reviews'] . ' reviews added (Whole Trades included).',
            'green'
        );

        return EXIT_SUCCESS;
    }
}
