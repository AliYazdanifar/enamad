<?php

namespace App\Http\Controllers;

use App\Models\DomainEmail;
use App\Services\EnamadService;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class DomainEmailController extends Controller
{
    public function index()
    {

        $endPage = 6667;
        $enamad = new EnamadService();
        for ($i = 1; $i <= $endPage; $i++) {
            $result = $enamad->getEmails(3500);

            if (empty($result)) {
                Log::error('array is empty : ' . serialize($result));
                throw new Exception('array is empty!');
            }

            DomainEmail::insert($result);

            echo "inserted!";
        }
    }
}
