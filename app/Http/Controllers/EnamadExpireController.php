<?php

namespace App\Http\Controllers;

use App\Models\EnamadExpire;
use App\Services\EnamadService2;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\File;

class EnamadExpireController extends Controller
{
    public function index()
    {
        $enamad = new EnamadService2();
        $domains = EnamadExpire::all();
        foreach ($domains as $domain) {
            $href = 'https://trustseal.enamad.ir/' . $domain->url_params;

            $result = $enamad->secondPage($href, $domain->domain);

            $date = Verta::parse($result['validTo'])->datetime()->format('Y-m-d');

            $txt = $date;
            File::put(public_path($domain->domain), $txt);

            $domain->exp_date = $result['validTo'];
            $domain->save();

            echo $domain->domain ." updated!".PHP_EOL;
        }

    }
}
