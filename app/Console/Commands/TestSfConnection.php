<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SuccessFactors\SuccessFactorsClient;

class TestSfConnection extends Command
{
    // ✅ THIS DEFINES THE ARTISAN COMMAND NAME
    protected $signature = 'sf:test-connection';

    protected $description = 'Test SuccessFactors API connection';

    public function handle(SuccessFactorsClient $client)
    {
        $response = $client->get('User', [
            '$top' => 1,
            '$format' => 'json',
        ]);

        $this->info('Connected successfully!');
        $this->line(json_encode($response, JSON_PRETTY_PRINT));
    }
}
