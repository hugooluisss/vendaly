<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\{ServerRequest, Stream};

use function json_decode;
use function json_encode;
use function PHPUnit\Framework\assertSame;

final class CatalogOptionsCest
{
    public function productEndpointAcceptsAndReturnsOptions(FunctionalTester $tester): void
    {
        $email = 'options-' . uniqid() . '@example.com';
        $auth = $this->request($tester, 'POST', '/auth/register', ['email' => $email, 'password' => 'password-123']);
        assertSame(201, $auth->getStatusCode());
        $token = json_decode($auth->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['access_token'];

        $business = $this->request($tester, 'POST', '/businesses', ['name' => 'Options ' . uniqid()], $token);
        assertSame(201, $business->getStatusCode());
        $businessId = json_decode($business->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['business']['id'];
        $category = $this->request($tester, 'POST', "/businesses/{$businessId}/categories", ['name' => 'Drinks'], $token);
        assertSame(201, $category->getStatusCode());
        $categoryId = json_decode($category->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['category']['id'];

        $response = $this->request($tester, 'POST', "/businesses/{$businessId}/products", [
            'category_id' => $categoryId,
            'name' => 'Coffee',
            'price' => '50.00',
            'options' => [[
                'name' => 'Sugar type',
                'selection_type' => 'single',
                'required' => true,
                'values' => [
                    ['name' => 'Brown sugar', 'price_delta' => '0'],
                    ['name' => 'Splenda', 'price_delta' => '5'],
                ],
            ]],
        ], $token);

        assertSame(201, $response->getStatusCode());
        $product = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['product'];
        assertSame('Sugar type', $product['options'][0]['name']);
        assertSame('single', $product['options'][0]['selection_type']);
        assertSame(true, $product['options'][0]['required']);
        assertSame('Splenda', $product['options'][0]['values'][1]['name']);
        assertSame('5', $product['options'][0]['values'][1]['price_delta']);
    }

    public function businessProfileAcceptsFulfillmentMethods(FunctionalTester $tester): void
    {
        $email = 'fulfillment-' . uniqid() . '@example.com';
        $auth = $this->request($tester, 'POST', '/auth/register', ['email' => $email, 'password' => 'password-123']);
        assertSame(201, $auth->getStatusCode());
        $token = json_decode($auth->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['access_token'];
        $business = $this->request($tester, 'POST', '/businesses', ['name' => 'Fulfillment ' . uniqid()], $token);
        assertSame(201, $business->getStatusCode());
        $businessId = json_decode($business->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['business']['id'];
        $response = $this->request($tester, 'PATCH', "/businesses/{$businessId}", ['pickup_enabled' => false, 'delivery_enabled' => true], $token);
        assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['business'];
        assertSame(false, $data['pickup_enabled']);
        assertSame(true, $data['delivery_enabled']);
    }

    public function businessProfileManagesFeesAndPaymentMethods(FunctionalTester $tester): void
    {
        $email = 'payments-' . uniqid() . '@example.com';
        $auth = $this->request($tester, 'POST', '/auth/register', ['email' => $email, 'password' => 'password-123']);
        $token = json_decode($auth->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['access_token'];
        $business = $this->request($tester, 'POST', '/businesses', ['name' => 'Payments ' . uniqid()], $token);
        $businessId = json_decode($business->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['business']['id'];
        $updated = $this->request($tester, 'PATCH', "/businesses/{$businessId}", ['delivery_enabled' => true, 'delivery_fee' => 30], $token);
        assertSame('30.00', json_decode($updated->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['business']['delivery_fee']);
        $created = $this->request($tester, 'POST', "/businesses/{$businessId}/payment-methods", ['name' => 'Transferencia'], $token);
        assertSame(201, $created->getStatusCode());
        $methods = json_decode($this->request($tester, 'GET', "/businesses/{$businessId}/payment-methods", [], $token)->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['payment_methods'];
        assertSame(['Efectivo', 'Transferencia'], array_column($methods, 'name'));
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
