<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\DTO;

/**
 * Immutable result of a staging operation (insert, update, duplicate, error).
 *
 * Factory methods provide semantic construction:
 *   StagingResult::inserted($id)
 *   StagingResult::updated($id)
 *   StagingResult::duplicate($id)
 *   StagingResult::error(StagingError $error)
 *
 * @since 2.1.0
 */
class StagingResult
{
    const STATUS_INSERTED  = 'inserted';
    const STATUS_UPDATED   = 'updated';
    const STATUS_DUPLICATE = 'duplicate';
    const STATUS_ERROR     = 'error';

    /** @var string One of the STATUS_* constants */
    private $status;

    /** @var int Row ID in the staging table (0 for errors) */
    private $id;

    /** @var StagingError|null Present when status is STATUS_ERROR */
    private $error;

    private function __construct(string $status, int $id = 0, ?StagingError $error = null)
    {
        $this->status = $status;
        $this->id = $id;
        $this->error = $error;
    }

    // -- Factory methods ----------------------------------------------------

    public static function inserted(int $id): self
    {
        return new self(self::STATUS_INSERTED, $id);
    }

    public static function updated(int $id): self
    {
        return new self(self::STATUS_UPDATED, $id);
    }

    public static function duplicate(int $id): self
    {
        return new self(self::STATUS_DUPLICATE, $id);
    }

    public static function error(StagingError $error): self
    {
        return new self(self::STATUS_ERROR, 0, $error);
    }

    // -- Getters ------------------------------------------------------------

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getError(): ?StagingError
    {
        return $this->error;
    }

    public function isOk(): bool
    {
        return $this->status !== self::STATUS_ERROR;
    }

    /**
     * Serialize to array (for hook_invoke data exchange).
     *
     * @return array{status: string, id: int, error: array|null}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'id'     => $this->id,
            'error'  => $this->error ? $this->error->toArray() : null,
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
        $status = $data['status'] ?? self::STATUS_ERROR;
        $id = (int)($data['id'] ?? 0);
        $error = isset($data['error']) && is_array($data['error'])
            ? StagingError::fromArray($data['error'])
            : null;

        return new self($status, $id, $error);
    }
}
