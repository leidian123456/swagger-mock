<?php
/*
 * This file is part of Swagger Mock.
 *
 * (c) Igor Lazarev <strider2038@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\OpenAPI\Parsing\Type\Composite;

use App\Mock\Parameters\Schema\Type\Composite\ArrayType;
use App\OpenAPI\Parsing\ParsingException;
use App\OpenAPI\Parsing\SpecificationAccessor;
use App\OpenAPI\Parsing\SpecificationPointer;
use App\OpenAPI\Parsing\Type\Composite\ArrayTypeParser;
use App\Tests\Utility\TestCase\ContextualParserTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ArrayTypeParserTest extends TestCase
{
    use ContextualParserTestCaseTrait;

    private const ITEMS_SCHEMA_TYPE = 'itemsSchemaType';
    private const ITEMS_SCHEMA = [
        'type' => self::ITEMS_SCHEMA_TYPE
    ];
    private const VALID_SCHEMA_WITH_PARAMETERS = [
        'type' => 'array',
        'items' => self::ITEMS_SCHEMA,
        'minItems' => self::MIN_ITEMS,
        'maxItems' => self::MAX_ITEMS,
        'uniqueItems' => true,
    ];
    private const VALID_SCHEMA_WITHOUT_PARAMETERS = [
        'type' => 'array',
        'items' => self::ITEMS_SCHEMA,
    ];
    private const SCHEMA_WITHOUT_ITEMS = [
        'type' => 'array',
    ];
    private const MIN_ITEMS = 5;
    private const MAX_ITEMS = 10;

    protected function setUp(): void
    {
        $this->setUpContextualParser();
    }

    /** @test */
    public function parsePointedSchema_validSchemaWithItemsAndParameters_itemSchemaParsedByTypeParser(): void
    {
        $parser = $this->createArrayTypeParser();
        $itemsType = $this->givenContextualParser_parsePointedSchema_returnsObject();
        $specification = new SpecificationAccessor(self::VALID_SCHEMA_WITH_PARAMETERS);

        /** @var ArrayType $type */
        $type = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(ArrayType::class, $type);
        $this->assertContextualParser_parsePointedSchema_wasCalledOnceWithSpecificationAndPointerPath($specification, ['items']);
        $this->assertSame($itemsType, $type->items);
        $this->assertSame(self::MIN_ITEMS, $type->minItems);
        $this->assertSame(self::MAX_ITEMS, $type->maxItems);
        $this->assertTrue($type->uniqueItems);
    }

    /** @test */
    public function parsePointedSchema_validSchemaWithItemsAndNoParameters_itemSchemaParsedByTypeParserAndParametersSetToDefaults(): void
    {
        $parser = $this->createArrayTypeParser();
        $itemsType = $this->givenContextualParser_parsePointedSchema_returnsObject();
        $specification = new SpecificationAccessor(self::VALID_SCHEMA_WITHOUT_PARAMETERS);

        /** @var ArrayType $type */
        $type = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(ArrayType::class, $type);
        $this->assertContextualParser_parsePointedSchema_wasCalledOnceWithSpecificationAndPointerPath($specification, ['items']);
        $this->assertSame($itemsType, $type->items);
        $this->assertSame(0, $type->minItems);
        $this->assertSame(0, $type->maxItems);
        $this->assertFalse($type->uniqueItems);
    }

    /** @test */
    public function parsePointedSchema_noItemsInSchema_exceptionThrown(): void
    {
        $parser = $this->createArrayTypeParser();
        $specification = new SpecificationAccessor(self::SCHEMA_WITHOUT_ITEMS);

        $this->expectException(ParsingException::class);
        $this->expectExceptionMessage('Section "items" is required');

        $parser->parsePointedSchema($specification, new SpecificationPointer());
    }

    private function createArrayTypeParser(): ArrayTypeParser
    {
        return new ArrayTypeParser($this->contextualParser);
    }
}
