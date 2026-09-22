<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cron_trigger_rejects_missing_or_invalid_key(): void
    {
        config(['services.cron.key' => 'test_cron_secret_key']);

        $response1 = $this->getJson('/cron/run');
        $response1->assertStatus(401);

        $response2 = $this->getJson('/cron/run?key=wrong_secret');
        $response2->assertStatus(401);
    }

    public function test_cron_trigger_executes_scheduler_with_valid_key(): void
    {
        $secret = 'test_cron_secret_key';
        config(['services.cron.key' => $secret]);

        $response = $this->getJson('/cron/run?key='.$secret);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Scheduler executed successfully in-memory.',
        ]);
    }
}
