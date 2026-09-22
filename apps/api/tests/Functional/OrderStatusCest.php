<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\{ServerRequest, Stream};

use function json_decode;
use function json_encode;
use function PHPUnit\Framework\assertSame;

final class OrderStatusCest
{
    public function ownerCanManageOrderStatuses(FunctionalTester $tester): void
    {
        $email = 'status-' . uniqid() . '@example.com';
        $auth = $this->request($tester, 'POST', '/auth/register', ['email' => $email, 'password' => 'password-123']);
        $token = json_decode($auth->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['access_token'];
        $business = $this->request($tester, 'POST', '/businesses', ['name' => 'Status ' . uniqid()], $token);
        $businessId = json_decode($business->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['business']['id'];

        $listed = $this->request($tester, 'GET', "/businesses/{$businessId}/order-statuses", [], $token);
        assertSame(200, $listed->getStatusCode());
        $statuses = json_decode($listed->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['order_statuses'];
        assertSame(['Creado', 'Elaborando', 'Entregado', 'Cancelado'], array_column($statuses, 'name'));

        $created = $this->request($tester, 'POST', "/businesses/{$businessId}/order-statuses", ['name' => 'En camino', 'color' => '#123456'], $token);
        assertSame(201, $created->getStatusCode());
        $statusId = json_decode($created->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['order_status']['id'];
        assertSame(200, $this->request($tester, 'PATCH', "/businesses/{$businessId}/order-statuses/{$statusId}", ['name' => 'En ruta'], $token)->getStatusCode());
        assertSame(200, $this->request($tester, 'POST', "/businesses/{$businessId}/order-statuses/{$statusId}/default", [], $token)->getStatusCode());
        $pdo = new \PDO(getenv('DATABASE_URL') ?: 'pgsql:host=postgres;port=5432;dbname=vendaly', getenv('POSTGRES_USER') ?: 'vendaly', getenv('POSTGRES_PASSWORD') ?: 'vendaly');
        $number = (int) $pdo->query("UPDATE businesses SET next_order_number = next_order_number + 1 WHERE id = {$businessId} RETURNING next_order_number - 1")->fetchColumn();
        $insert = $pdo->prepare('INSERT INTO orders (business_id, order_number, status_id) VALUES (?, ?, ?) RETURNING id');
        $insert->execute([$businessId, $number, $statuses[0]['id']]);
        $orderId = (int) $insert->fetchColumn();
        $listedOrder = json_decode($this->request($tester, 'GET', "/businesses/{$businessId}/orders", [], $token)->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['orders'][0];
        assertSame($number, $listedOrder['order_number']);
        assertSame('Creado', $listedOrder['status']['name']);
        assertSame(200, $this->request($tester, 'PATCH', "/businesses/{$businessId}/orders/{$orderId}/status", ['status_id' => $statusId], $token)->getStatusCode());
        assertSame(422, $this->request($tester, 'DELETE', "/businesses/{$businessId}/order-statuses/{$statusId}", [], $token)->getStatusCode());
    }

    private function request(FunctionalTester $tester, string $method, string $uri, array $body, ?string $token = null): \Psr\Http\Message\ResponseInterface
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($token !== null) $headers['Authorization'] = 'Bearer ' . $token;
        $stream = new Stream(); $stream->write(json_encode($body, JSON_THROW_ON_ERROR));
        return $tester->sendRequest(new ServerRequest([], [], [], [], $body, $method, $uri, $headers, $stream));
    }
}
