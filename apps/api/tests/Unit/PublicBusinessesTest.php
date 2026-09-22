<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Entity\Business;
use App\Domain\Repository\BusinessRepositoryInterface;
use App\Web\PublicBusinesses\Action;
use HttpSoft\Message\{ResponseFactory, ServerRequest, StreamFactory};
use PHPUnit\Framework\TestCase;

final class PublicBusinessesTest extends TestCase
{
    public function testDirectoryIncludesCoordinatesAndForwardsVisitorPosition(): void
    {
        $business = new Business();
        $business->name = 'Cafe';
        $business->slug = 'cafe';
        $business->latitude = 19.4;
        $business->longitude = -99.1;
        $repository = new DirectoryBusinesses([$business]);
        $response = (new Action($repository, new ResponseFactory(), new StreamFactory()))->__invoke(
            (new ServerRequest())->withQueryParams(['lat' => '19.5', 'lng' => '-99.2']),
        );
        self::assertSame([19.5, -99.2], $repository->position);
        $payload = json_decode((string) $response->getBody(), true);
        self::assertSame(19.4, $payload['businesses'][0]['latitude']);
        self::assertSame(-99.1, $payload['businesses'][0]['longitude']);
    }

    public function testDirectoryForwardsNameFilter(): void
    {
        $repository = new DirectoryBusinesses([]);
        (new Action($repository, new ResponseFactory(), new StreamFactory()))->__invoke(
            (new ServerRequest())->withQueryParams(['name' => 'cafe']),
        );
        self::assertSame('cafe', $repository->name);
    }
}

final class DirectoryBusinesses implements BusinessRepositoryInterface
{
    public ?array $position = null;
    public ?string $name = null;
    public function __construct(private array $businesses) {}
    public function create(Business $entity): Business
    {
        return $entity;
    }
    public function findById(int $id): ?Business
    {
        return null;
    }
    public function findPublishedBySlug(string $slug): ?Business
    {
        return null;
    }
    public function findHours(int $businessId): array
    {
        return [];
    }
    public function findPublishedDirectory(?string $category, ?string $location, ?float $latitude = null, ?float $longitude = null, ?string $name = null): array
    {
        $this->position = $latitude === null || $longitude === null ? null : [$latitude, $longitude];
        $this->name = $name;
        return $this->businesses;
    }
}
