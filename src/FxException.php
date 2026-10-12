<?php

declare(strict_types=1);

namespace NetArz\Fx;

/** An error answer from the API (its JSON `error` object). */
final class FxException extends \RuntimeException
{
    public readonly ?string $errorCode;
    public readonly ?string $docs;
    /** Present on ip_not_allowed / origin_required: the address the API saw. */
    public readonly ?string $seenIp;

    /** @param array<string, mixed> $error */
    public function __construct(public readonly int $status, array $error)
    {
        $this->errorCode = isset($error['code']) ? (string) $error['code'] : null;
        $this->docs = isset($error['docs']) ? (string) $error['docs'] : null;
        $this->seenIp = isset($error['ip']) ? (string) $error['ip'] : null;

        parent::__construct(($this->errorCode ?? 'error').': '.($error['message'] ?? ''), $status);
    }
}
