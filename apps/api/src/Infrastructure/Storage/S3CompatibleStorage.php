<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Repository\ObjectStorageInterface;
use RuntimeException;

final class S3CompatibleStorage implements ObjectStorageInterface
{
    public function put(string $key, string $contents, string $contentType): string
    {
        $endpoint = rtrim(getenv('S3_ENDPOINT') ?: 'http://minio:9000', '/');
        $bucket = getenv('S3_BUCKET') ?: 'vendaly';
        $url = $endpoint . '/' . $bucket . '/' . ltrim($key, '/');
        $context = stream_context_create(['http' => [
            'method' => 'PUT',
            'header' => "Content-Type: {$contentType}\r\nContent-Length: " . strlen($contents),
            'content' => $contents,
            'ignore_errors' => true,
        ]]);
        if (file_get_contents($url, false, $context) === false) {
            throw new RuntimeException('Unable to upload object.');
        }
        return rtrim(getenv('S3_PUBLIC_URL') ?: $endpoint . '/' . $bucket, '/') . '/' . ltrim($key, '/');
    }
}
