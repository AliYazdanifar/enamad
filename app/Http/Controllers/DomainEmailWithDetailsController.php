<?php

namespace App\Http\Controllers;

use App\Models\DomainEmailWithDetails;
use App\Services\EnamadService2;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Symfony\Component\VarDumper\VarDumper;

class DomainEmailWithDetailsController extends Controller
{
    public function index()
    {
        $endPage = 5;
        $enamad = new EnamadService2();
        for ($i = 1; $i <= $endPage; $i++) {
            $result = $enamad->getEmails($i);
            if (empty($result)) {
                Log::error('array is empty : ' . serialize($result));
                throw new Exception('array is empty!');
            }

            DomainEmailWithDetails::insert($result);
            echo "inserted!";
        }

    }
}
