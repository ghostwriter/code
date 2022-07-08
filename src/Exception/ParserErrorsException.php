<?php

declare(strict_types=1);

namespace Ghostwriter\Code\Exception;

use Ghostwriter\Code\Contract\ExceptionInterface;
use Ghostwriter\Option\Contract\OptionInterface;
use PhpParser\Error;
use RuntimeException;
use function array_map;
use function implode;

final class ParserErrorsException extends RuntimeException implements ExceptionInterface
{
    /**
     * @var Error[]
     */
    private array $errors;

    private OptionInterface $parsedFile;

    /**
     * @param Error[] $errors
     */
    public function __construct(array $errors, OptionInterface $parsedFile)
    {
        $this->errors = $errors;
        $this->parsedFile = $parsedFile;

        parent::__construct(
            implode(', ', array_map(static fn (Error $error): string => $error->getMessage(), $errors))
        );
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getParsedFile(): string
    {
        /** @var string */
        return $this->parsedFile->unwrapOr('');
    }
}
