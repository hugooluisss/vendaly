<?php

declare(strict_types=1);

namespace App\Web\PublicOrders;

use App\Domain\Service\{OrderService, WhatsAppOrderLink};
use DomainException;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface, StreamFactoryInterface};

final readonly class Action
{
    public function __construct(
        private OrderService $service,
        private ResponseFactoryInterface $responses,
        private StreamFactoryInterface $streams,
    ) {}
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $input = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $data = $this->service->create((string) ($input['slug'] ?? ''), $input);
            $out = ['order_id' => $data['order']->id, 'whatsapp_url' => WhatsAppOrderLink::generate($data['order'], $data['items'], $data['whatsapp_number'], $data['wallet_enabled'], $data['wallet_credited'], $data['wallet_redeemed'], $data['wallet_balance']), 'total' => $data['order']->total];
            if ($data['wallet_enabled']) {
                $out['wallet_credited'] = $data['wallet_credited'];
                $out['wallet_redeemed'] = $data['wallet_redeemed'];
                $out['wallet_balance'] = $data['wallet_balance'];
            }
            return $this->responses->createResponse(201)->withHeader('Content-Type', 'application/json')->withBody($this->streams->createStream((string) json_encode($out)));
        } catch (DomainException|\JsonException $e) {
            return $this->responses->createResponse(422)->withHeader('Content-Type', 'application/json')->withBody($this->streams->createStream(json_encode(['error' => $e->getMessage()])));
        }
    }
}
