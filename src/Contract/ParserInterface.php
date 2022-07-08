<?php

declare(strict_types=1);

namespace Ghostwriter\Code\Contract;

use Ghostwriter\Collection\Collection;
use PhpParser\Node\Stmt;

interface ParserInterface
{
    /**
     * @return Collection<Stmt>
     */
    public function parse(string $code): Collection;
}
