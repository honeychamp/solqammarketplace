<?php

namespace App\Controllers;

use App\Services\Marketing\MetaCatalogFeedService;

class FeedController extends BaseController
{
    public function metaCsv()
    {
        try {
            $csv = (new MetaCatalogFeedService())->buildCsv();
        } catch (\Throwable $e) {
            log_message('error', 'Meta catalog feed: ' . $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
                ->setBody('Feed unavailable');
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'inline; filename="meta.csv"')
            ->setHeader('Cache-Control', 'public, max-age=300')
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setBody($csv);
    }
}
