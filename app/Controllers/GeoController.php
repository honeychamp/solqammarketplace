<?php

namespace App\Controllers;

use App\Services\Card\SolqamGeoCatalog;
use App\Services\Platform\SchemaHeal;

class GeoController extends BaseController
{
    public function cities(string $divisionCode)
    {
        SchemaHeal::ensureCardNetwork();

        return $this->response->setJSON(SolqamGeoCatalog::citiesForDivision($divisionCode));
    }
}
