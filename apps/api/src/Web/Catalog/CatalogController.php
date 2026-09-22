<?php

declare(strict_types=1);

namespace App\Web\Catalog;

use App\Domain\Exception\ForbiddenException;
use App\Domain\Service\{CategoryService, ProductService};
use App\Web\Shared\PublicEntityMapper;
use DomainException;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};
use Yiisoft\Router\CurrentRoute;

final readonly class CatalogController
{
    public function __construct(private CategoryService $categories, private ProductService $products, private ResponseFactoryInterface $responses, private StreamFactoryInterface $streams, private CurrentRoute $route) {}
    public function categories(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn() => [
            'categories' => array_map(PublicEntityMapper::category(...), $this->categories->list($this->uid($request), $this->bid())),
        ]);
    }

    public function createCategory(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        return $this->run(fn() => [
            'category' => PublicEntityMapper::category($this->categories->create(
                $this->uid($request),
                $this->bid(),
                (string) ($body['name'] ?? ''),
                (int) ($body['position'] ?? 0),
            )),
        ], 201);
    }

    public function updateCategory(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn() => [
            'category' => PublicEntityMapper::category($this->categories->update($this->uid($request), $this->bid(), $this->id(), $this->body($request))),
        ]);
    }

    public function deleteCategory(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn() => [
            'result' => $this->categories->delete($this->uid($request), $this->bid(), $this->id()),
        ]);
    }

    public function reorderCategories(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        return $this->run(fn() => [
            'categories' => array_map(PublicEntityMapper::category(...), $this->categories->reorder($this->uid($request), $this->bid(), $body['positions'] ?? [])),
        ]);
    }

    public function products(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn() => [
            'products' => array_map(PublicEntityMapper::product(...), $this->products->list($this->uid($request), $this->bid())),
        ]);
    }

    public function createProduct(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        [$image, $contentType] = $this->upload($request, 'image');
        return $this->run(fn() => [
            'product' => PublicEntityMapper::product($this->products->create($this->uid($request), $this->bid(), $body, $image, $contentType)),
        ], 201);
    }

    public function updateProduct(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        [$image, $contentType] = $this->upload($request, 'image');
        return $this->run(fn() => [
            'product' => PublicEntityMapper::product($this->products->update($this->uid($request), $this->bid(), $this->id(), $body, $image, $contentType)),
        ]);
    }

    public function deleteProduct(ServerRequestInterface $request): ResponseInterface
    {
        return $this->run(fn() => [
            'result' => $this->products->delete($this->uid($request), $this->bid(), $this->id()),
        ]);
    }

    public function activeProduct(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->body($request);
        return $this->run(fn() => [
            'product' => PublicEntityMapper::product($this->products->setActive(
                $this->uid($request),
                $this->bid(),
                $this->id(),
                (bool) ($body['is_active'] ?? true),
            )),
        ]);
    }

    private function run(callable $callback, int $status = 200): ResponseInterface
    {
        try {
            return $this->json($callback(), $status);
        } catch (DomainException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                $e instanceof ForbiddenException ? 403 : 422,
            );
        }
    }
    private function body(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            if (is_string($parsed['ingredients'] ?? null)) {
                $parsed['ingredients'] = json_decode($parsed['ingredients'], true);
            }
            if (is_string($parsed['options'] ?? null)) {
                $parsed['options'] = json_decode($parsed['options'], true);
            }
            return $parsed;
        }
        $body = json_decode((string) $request->getBody(), true);
        return is_array($body) ? $body : [];
    }
    private function upload(ServerRequestInterface $request, string $name): array
    {
        $file = $request->getUploadedFiles()[$name] ?? null;
        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            return [null, 'application/octet-stream'];
        }
        return [
            (string) $file->getStream(),
            $file->getClientMediaType() ?: 'application/octet-stream',
        ];
    }
    private function uid(ServerRequestInterface $request): int
    {
        return (int) $request->getAttribute('user_id');
    }
    private function bid(): int
    {
        return (int) $this->route->getArgument('businessId');
    }
    private function id(): int
    {
        return (int) $this->route->getArgument('id');
    }
    private function json(array $data, int $status): ResponseInterface
    {
        return $this->responses->createResponse($status)->withHeader('Content-Type', 'application/json')->withBody($this->streams->createStream(json_encode($data, JSON_THROW_ON_ERROR)));
    }
}
