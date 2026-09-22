<?php

declare(strict_types=1);

namespace App\Web\Business;

use App\Domain\Exception\ForbiddenException;
use App\Domain\Service\BusinessService;
use App\Web\Shared\PublicEntityMapper;
use DomainException;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};
use Yiisoft\Router\CurrentRoute;

final readonly class BusinessController
{
    public function __construct(private BusinessService $service, private ResponseFactoryInterface $responses, private StreamFactoryInterface $streams, private CurrentRoute $route)
    {
    }
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['business' => PublicEntityMapper::business($this->service->create($this->userId($request), (string) ($this->body($request)['name'] ?? '')))], 201); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function me(ServerRequestInterface $request): ResponseInterface
    {
        $result = $this->service->findByOwner($this->userId($request));
        return $result === null ? $this->responses->createResponse(404) : $this->json([
            'business' => PublicEntityMapper::business($result['business'], $this->service->listPaymentMethods($this->userId($request), (int) $result['business']->id), $this->service->listOrderStatuses($this->userId($request), (int) $result['business']->id)),
            'hours' => array_map(PublicEntityMapper::hours(...), $result['hours']),
        ]);
    }
    public function paymentMethods(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['payment_methods' => array_map(PublicEntityMapper::paymentMethod(...), $this->service->listPaymentMethods($this->userId($request), $this->id()))]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function createPaymentMethod(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['payment_method' => PublicEntityMapper::paymentMethod($this->service->addPaymentMethod($this->userId($request), $this->id(), (string) ($this->body($request)['name'] ?? '')))], 201); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function updatePaymentMethod(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['payment_method' => PublicEntityMapper::paymentMethod($this->service->updatePaymentMethod($this->userId($request), $this->id(), (int) $this->route->getArgument('methodId'), $this->body($request)))]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function deletePaymentMethod(ServerRequestInterface $request): ResponseInterface
    {
        try { $this->service->deletePaymentMethod($this->userId($request), $this->id(), (int) $this->route->getArgument('methodId')); return $this->json(['result' => true]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function orderStatuses(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['order_statuses' => array_map(PublicEntityMapper::orderStatus(...), $this->service->listOrderStatuses($this->userId($request), $this->id()))]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function createOrderStatus(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['order_status' => PublicEntityMapper::orderStatus($this->service->addOrderStatus($this->userId($request), $this->id(), $this->body($request)))], 201); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function updateOrderStatus(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $status = $this->service->updateOrderStatus($this->userId($request), $this->id(), (int) $this->route->getArgument('statusId'), $this->body($request));
            if (($this->body($request)['is_default'] ?? false) === true) $status = $this->service->setDefaultOrderStatus($this->userId($request), $this->id(), (int) $status->id);
            return $this->json(['order_status' => PublicEntityMapper::orderStatus($status)]);
        } catch (DomainException $e) { return $this->error($e); }
    }
    public function setDefaultOrderStatus(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['order_status' => PublicEntityMapper::orderStatus($this->service->setDefaultOrderStatus($this->userId($request), $this->id(), (int) $this->route->getArgument('statusId')))]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function reorderOrderStatuses(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['order_statuses' => array_map(PublicEntityMapper::orderStatus(...), $this->service->reorderOrderStatuses($this->userId($request), $this->id(), $this->body($request)['positions'] ?? []))]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function deleteOrderStatus(ServerRequestInterface $request): ResponseInterface
    {
        try { $this->service->deleteOrderStatus($this->userId($request), $this->id(), (int) $this->route->getArgument('statusId')); return $this->json(['result' => true]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        try {
            [$contents, $type] = $this->upload($request, 'logo');
            [$cover, $coverType] = $this->upload($request, 'cover_image');
            return $this->json(['business' => PublicEntityMapper::business($this->service->updateProfile($this->userId($request), $this->id(), $this->body($request), $contents, $type, $cover, $coverType))]);
        } catch (DomainException $e) { return $this->error($e); }
    }
    public function hours(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['hours' => array_map(PublicEntityMapper::hours(...), $this->service->updateHours($this->userId($request), $this->id(), $this->body($request)['days'] ?? []))]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    public function publish(ServerRequestInterface $request): ResponseInterface
    {
        try { return $this->json(['business' => PublicEntityMapper::business($this->service->setPublished($this->userId($request), $this->id(), (bool) ($this->body($request)['published'] ?? true)))]); }
        catch (DomainException $e) { return $this->error($e); }
    }
    private function body(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) return $parsed;
        $body = json_decode((string) $request->getBody(), true);
        return is_array($body) ? $body : [];
    }
    private function upload(ServerRequestInterface $request, string $name): array
    {
        $file = $request->getUploadedFiles()[$name] ?? null;
        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) return [null, 'application/octet-stream'];
        return [(string) $file->getStream(), $file->getClientMediaType() ?: 'application/octet-stream'];
    }
    private function userId(ServerRequestInterface $request): int { return (int) $request->getAttribute('user_id'); }
    private function id(): int { return (int) $this->route->getArgument('id'); }
    private function error(DomainException $e): ResponseInterface { return $this->json(['error' => $e->getMessage()], $e instanceof ForbiddenException ? 403 : 422); }
    private function json(array $data, int $status = 200): ResponseInterface { return $this->responses->createResponse($status)->withHeader('Content-Type', 'application/json')->withBody($this->streams->createStream(json_encode($data, JSON_THROW_ON_ERROR))); }
}
