<?php

namespace App\Http\Controllers;

use App\Models\DomainEmail;
use App\Services\OpenSslService;

class SslInfoController extends Controller
{
    public function index()
    {
        $domains = DomainEmail::limit(5)->get();
        dd($domains);
        foreach ($domains as $domain) {
            $x = new OpenSslService();
            $out = $x->getInfo($domain->domain);
            dd($out);
        }

    }
}
