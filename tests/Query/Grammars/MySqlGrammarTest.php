<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Query\Grammars;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Staudenmeir\LaravelAdjacencyList\Query\Grammars\MySqlGrammar;

class MySqlGrammarTest extends TestCase
{
    #[DataProvider('pivotColumnNullValueProvider')]
    public function testCompilePivotColumnNullValue(string $expectedCast, string $typeName, string $type): void
    {
        $grammar = (new ReflectionClass(MySqlGrammar::class))->newInstanceWithoutConstructor();

        $this->assertSame("cast(null as $expectedCast)", $grammar->compilePivotColumnNullValue($typeName, $type));
    }

    public static function pivotColumnNullValueProvider(): array
    {
        return [
            'int' => ['signed', 'int', 'int'],
            'int unsigned' => ['signed', 'int', 'int unsigned'],
            'bigint' => ['signed', 'bigint', 'bigint'],
            'bigint unsigned' => ['signed', 'bigint', 'bigint unsigned'],
            'smallint' => ['signed', 'smallint', 'smallint'],
            'tinyint' => ['signed', 'tinyint', 'tinyint'],
            'boolean' => ['signed', 'boolean', 'boolean'],
            'double' => ['double', 'double', 'double'],
            'double unsigned' => ['double', 'double', 'double unsigned'],
            'float' => ['double', 'float', 'float'],
            'float unsigned' => ['double', 'float', 'float unsigned'],
            'decimal' => ['decimal(8, 2)', 'decimal', 'decimal(8,2)'],
            'timestamp' => ['datetime', 'timestamp', 'timestamp'],
            'varchar' => ['char(65535)', 'varchar', 'varchar'],
            'default' => ['text', 'text', 'text'],
        ];
    }
}
