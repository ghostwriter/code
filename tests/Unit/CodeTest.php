<?php

declare(strict_types=1);

namespace Ghostwriter\Code\Tests\Unit;

use Ghostwriter\Code\Code;

/**
 * @coversDefaultClass \Ghostwriter\Code\Code
 *
 * @internal
 *
 * @small
 */
final class CodeTest extends AbstractTestCase
{
    /** @covers ::test */
    public function test(): void
    {
        self::assertTrue((new Code())->test());
    }
}
