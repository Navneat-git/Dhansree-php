<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WireContractTest extends TestCase
{
    public function test_health_endpoint_returns_standard_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'timestamp',
                ],
            ]);

        $this->assertEquals('healthy', $response->json('data.status'));
    }

    public function test_not_found_returns_standard_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/non-existent-endpoint');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Resource not found',
                ],
            ])
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'requestId',
                ],
            ]);
    }

    public function test_method_not_allowed_returns_standard_error_envelope(): void
    {
        $response = $this->postJson('/api/v1/health');

        $response->assertStatus(405)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'error' => [
                    'code' => 'METHOD_NOT_ALLOWED',
                    'message' => 'Method not allowed',
                ],
            ]);
    }

    public function test_validation_error_returns_422_with_details_array(): void
    {
        Route::post('/api/v1/test-validation', function () {
            request()->validate([
                'email' => 'required|email',
                'name' => 'required|min:3',
            ]);
            return response()->json(['ok' => true]);
        });

        $response = $this->postJson('/api/v1/test-validation', [
            'email' => 'invalid-email',
            // name omitted
        ]);

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                ],
            ])
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => [
                        '*' => ['path', 'message'],
                    ],
                    'requestId',
                ],
            ]);

        $details = $response->json('error.details');
        $this->assertNotEmpty($details);
        $paths = array_column($details, 'path');
        $this->assertContains('email', $paths);
        $this->assertContains('name', $paths);
    }

    public function test_api_exception_renders_custom_code_and_status(): void
    {
        Route::get('/api/v1/test-custom-exception', function () {
            throw new ApiException('COUPON_EXPIRED', 'This coupon has expired', 400);
        });

        $response = $this->getJson('/api/v1/test-custom-exception');

        $response->assertStatus(400)
            ->assertJson([
                'error' => [
                    'code' => 'COUPON_EXPIRED',
                    'message' => 'This coupon has expired',
                ],
            ]);
    }
}
