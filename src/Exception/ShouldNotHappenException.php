<?php

declare(strict_types=1);

namespace Ghostwriter\Code\Exception;

use Ghostwriter\Code\Contract\ExceptionInterface;
use RuntimeException;

final class ShouldNotHappenException extends RuntimeException implements ExceptionInterface
{
}
