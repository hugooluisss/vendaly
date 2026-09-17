<?php

declare(strict_types=1);

namespace App\Web\PublicBusinesses;

use App\Domain\Repository\BusinessRepositoryInterface;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};

final readonly class Action
{
    public function __construct(
        private BusinessRepositoryInterface $businesses,
        private ResponseFactoryInterface $responses,
        private StreamFactoryInterface $streams,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $businesses = array_map(static fn($business) => [
            'name' => $business->name,
            'logo_url' => $business->logoUrl,
            'category' => $business->category,
            'location' => $business->location,
            'slug' => $business->slug,
            'latitude' => $business->latitude,
            'longitude' => $business->longitude,
        ], $this->businesses->findPublishedDirectory(
            isset($params['category']) ? (string) $params['category'] : null,
            isset($params['location']) ? (string) $params['location'] : null,
            isset($params['lat']) && is_numeric($params['lat']) ? (float) $params['lat'] : null,
            isset($params['lng']) && is_numeric($params['lng']) ? (float) $params['lng'] : null,
            isset($params['name']) ? (string) $params['name'] : null,
        ));

        return $this->responses->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streams->createStream(json_encode(['businesses' => $businesses], JSON_THROW_ON_ERROR)));
    }
}
