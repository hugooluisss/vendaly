<?php

declare(strict_types=1);

namespace App\Web\Health;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class Action
{
    public function __construct(private ResponseFactoryInterface $responses, private StreamFactoryInterface $streams) {}
    public function __invoke(): ResponseInterface
    {
        return $this->responses->createResponse()->withHeader('Content-Type', 'application/json')->withBody($this->streams->createStream('{"status":"ok"}'));
    }
}
