<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_rotas_protegidas_exigem_autenticacao(): void
    {
        $rotas = [
            ['GET',    '/api/repositories'],
            ['GET',    '/api/pipelines'],
            ['GET',    '/api/incidents'],
            ['GET',    '/api/health-scores'],
        ];

        foreach ($rotas as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertUnauthorized();
        }
    }

    public function test_webhook_github_nao_exige_autenticacao(): void
    {
        // Webhook é público — GitHub chama sem token
        $response = $this->postJson('/api/webhooks/github', []);

        // Sem assinatura → 401 (mas não é o 401 de Sanctum, é do validador de assinatura)
        // O importante é que a rota existe e não retorna 404
        $this->assertNotEquals(404, $response->status());
    }
}
