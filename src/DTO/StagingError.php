<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DTO;

/**
 * Typed error returned by staging operations.
 *
 * Each error carries a code, human-readable message, the offending field
 * (if applicable), and optional context for retry/debug.
 *
 * @since 2.1.0
 */
class StagingError
{
    const MISSING_REQUIRED   = 'STAGE_MISSING_REQUIRED';
    const INVALID_FORMAT     = 'STAGE_INVALID_FORMAT';
    const DUPLICATE          = 'STAGE_DUPLICATE';
    const DIFF_DUPLICATE     = 'STAGE_DIFF_DUPLICATE';
    const DB_ERROR           = 'STAGE_DB_ERROR';
    const VALIDATION_FAILED  = 'STAGE_VALIDATION_FAILED';
    const FA_NOT_AVAILABLE   = 'STAGE_FA_NOT_AVAILABLE';

    /** @var string One of the class constants */
    private $code;

    /** @var string Human-readable message */
    private $message;

    /** @var string Field name that caused the error, or '' */
    private $field;

    /** @var array<string, mixed> Additional context for retry/debug */
    private $context;

    public function __construct(string $code, string $message, string $field = '', array $context = [])
    {
        $this->code = $code;
        $this->message = $message;
        $this->field = $field;
        $this->context = $context;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Serialize to array (for hook_invoke data exchange).
     *
     * @return array{code: string, message: string, field: string, context: array}
     */
    public function toArray(): array
    {
        return [
            'code'    => $this->code,
            'message' => $this->message,
            'field'   => $this->field,
            'context' => $this->context,
        ];
    }

    /**
     * Reconstruct from array (for hook_invoke data exchange).
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['code'] ?? '',
            $data['message'] ?? '',
            $data['field'] ?? '',
            $data['context'] ?? []
        );
    }
}
