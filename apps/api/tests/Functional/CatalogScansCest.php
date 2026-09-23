<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\{ServerRequest, Stream};

use function PHPUnit\Framework\assertSame;

final class CatalogScansCest
{
    private ?\PDO $pdoConnection = null;

    public function publicEndpointRecordsOnlyPublishedBusinesses(FunctionalTester $tester): void
    {
        [$token, $businessId] = $this->registerBusiness($tester, 'scan-public');
        $statement = $this->pdo()->prepare('UPDATE businesses SET is_published = TRUE WHERE id = ?');
        $statement->execute([$businessId]);
        $business = $this->pdo()->query("SELECT slug FROM businesses WHERE id = {$businessId}")->fetch(\PDO::FETCH_ASSOC);

        assertSame(204, $this->request($tester, 'POST', '/public/catalog/' . $business['slug'] . '/scans')->getStatusCode());
        assertSame(404, $this->request($tester, 'POST', '/public/catalog/unknown-scan-slug/scans')->getStatusCode());
        [, $unpublishedId] = $this->registerBusiness($tester, 'scan-unpublished');
        $unpublished = $this->pdo()->prepare('SELECT slug FROM businesses WHERE id = ?');
        $unpublished->execute([$unpublishedId]);
        assertSame(404, $this->request($tester, 'POST', '/public/catalog/' . $unpublished->fetchColumn() . '/scans')->getStatusCode());
        assertSame(1, (int) $this->pdo()->query("SELECT COUNT(*) FROM catalog_scans WHERE business_id = {$businessId}")->fetchColumn());
    }

    public function statsAreRestrictedToBusinessMembersAndReturnEmptyShape(FunctionalTester $tester): void
    {
        [$ownerToken, $businessId] = $this->registerBusiness($tester, 'scan-owner');
        [$otherToken] = $this->registerBusiness($tester, 'scan-other');
        $ownerResponse = $this->request($tester, 'GET', "/businesses/{$businessId}/scans", [], $ownerToken);
        assertSame(200, $ownerResponse->getStatusCode());
        assertSame(['total' => 0, 'by_day' => []], json_decode((string) $ownerResponse->getBody(), true, 512, JSON_THROW_ON_ERROR));
        assertSame(403, $this->request($tester, 'GET', "/businesses/{$businessId}/scans", [], $otherToken)->getStatusCode());
    }

    private function registerBusiness(FunctionalTester $tester, string $prefix): array
    {
        $registered = $this->request($tester, 'POST', '/auth/register', ['email' => $prefix . '-' . uniqid() . '@example.test', 'password' => 'password-123']);
        $token = json_decode((string) $registered->getBody(), true, 512, JSON_THROW_ON_ERROR)['access_token'];
        $created = $this->request($tester, 'POST', '/businesses', ['name' => ucfirst($prefix) . ' business'], $token);
        return [$token, json_decode((string) $created->getBody(), true, 512, JSON_THROW_ON_ERROR)['business']['id']];
    }

    private function pdo(): \PDO
    {
        return $this->pdoConnection ??= new \PDO(getenv('DATABASE_URL'), getenv('POSTGRES_USER'), getenv('POSTGRES_PASSWORD'));
    }

    private function request(FunctionalTester $tester, string $method, string $uri, array $body = [], ?string $token = null): \Psr\Http\Message\ResponseInterface
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        $stream = new Stream();
        $stream->write(json_encode($body, JSON_THROW_ON_ERROR));
        return $tester->sendRequest(new ServerRequest([], [], [], [], $body, $method, $uri, $headers, $stream));
    }
}
