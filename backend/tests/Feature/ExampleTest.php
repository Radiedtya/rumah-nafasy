<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Smoke test: backend ini API-only (frontend Vue terpisah),
     * jadi cek endpoint API publik, bukan route web '/'.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->getJson('/api/v1/specializations');

        $response->assertStatus(200);
    }
}
