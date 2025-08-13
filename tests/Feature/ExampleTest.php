<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        // Test the health endpoint instead of root route
        $response = $this->get('/health');
        
        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'healthy'
                ]);
    }
}
