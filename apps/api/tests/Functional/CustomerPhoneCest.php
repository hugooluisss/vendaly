<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\{ServerRequest, Stream};

use function PHPUnit\Framework\assertSame;

final class CustomerPhoneCest
{
    public function ownerOrderHistoryIncludesCustomerPhone(FunctionalTester $tester): void
    {
        $registered = $this->request($tester, 'POST', '/auth/register', ['email' => 'phone-' . uniqid() . '@example.test', 'password' => 'password-123']);
        $token = json_decode((string) $registered->getBody(), true, 512, JSON_THROW_ON_ERROR)['access_token'];
        $created = $this->request($tester, 'POST', '/businesses', ['name' => 'Phone ' . uniqid()], $token);
        $businessId = json_decode((string) $created->getBody(), true, 512, JSON_THROW_ON_ERROR)['business']['id'];
        $pdo = new \PDO(getenv('DATABASE_URL'), getenv('POSTGRES_USER'), getenv('POSTGRES_PASSWORD'));
        $phone = '+525512345678';
        $query = $pdo->prepare('INSERT INTO customers (business_id, phone) VALUES (?, ?) RETURNING id');
        $query->execute([$businessId, $phone]);
        $customerId = (int) $query->fetchColumn();
        $query = $pdo->prepare('INSERT INTO orders (business_id, order_number, status_id, customer_id) SELECT ?, 1, id, ? FROM order_statuses WHERE business_id = ? AND is_default RETURNING id');
        $query->execute([$businessId, $customerId, $businessId]);
        $orderId = (int) $query->fetchColumn();
        $otherPhone = '+525587654321';
        $query = $pdo->prepare('INSERT INTO customers (business_id, phone) VALUES (?, ?) RETURNING id');
        $query->execute([$businessId, $otherPhone]);
        $otherCustomerId = (int) $query->fetchColumn();
        $query = $pdo->prepare('INSERT INTO orders (business_id, order_number, status_id, customer_id) SELECT ?, 2, id, ? FROM order_statuses WHERE business_id = ? AND is_default RETURNING id');
        $query->execute([$businessId, $otherCustomerId, $businessId]);
        $otherOrderId = (int) $query->fetchColumn();

        $response = $this->request($tester, 'GET', "/businesses/{$businessId}/orders", [], $token);
        assertSame(200, $response->getStatusCode());
        $orders = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR)['orders'];
        $phonesByOrder = array_column($orders, 'customer_phone', 'id');
        assertSame($phone, $phonesByOrder[$orderId]);
        assertSame($otherPhone, $phonesByOrder[$otherOrderId]);
    }

    private function request(FunctionalTester $tester, string $method, string $uri, array $body, ?string $token = null): \Psr\Http\Message\ResponseInterface
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($token !== null) $headers['Authorization'] = 'Bearer ' . $token;
        $stream = new Stream();
        $stream->write(json_encode($body, JSON_THROW_ON_ERROR));
        return $tester->sendRequest(new ServerRequest([], [], [], [], $body, $method, $uri, $headers, $stream));
    }
}
