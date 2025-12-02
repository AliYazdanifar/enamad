<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $domain = "genepo.ir";
        $contextOptions = [
            "ssl" => [
                "capture_peer_cert" => true,
                "verify_peer" => false,
                "verify_peer_name" => false,
                "SNI_enabled" => true,
                "peer_name" => $domain, // مهم برای SNI
            ]
        ];

        $context = stream_context_create($contextOptions);

        $client = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$client) {
            return [
                'domain' => $domain,
                'error' => "Connection failed: $errstr",
            ];
        }

        $params = stream_context_get_params($client);

        if (!isset($params['options']['ssl']['peer_certificate'])) {
            return [
                'domain' => $domain,
                'error' => "No certificate returned",
            ];
        }

        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

        dd([
            'domain' => $domain,
            'exp_date' => date('Y-m-d H:i:s', $cert['validTo_time_t']),
            'issuer' => $cert['issuer']['O'] ?? '',
        ]);
    }
}
