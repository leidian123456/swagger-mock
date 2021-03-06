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

use App\Mock\Parameters\Schema\Type\Composite\FreeFormObjectType;
use App\Mock\Parameters\Schema\Type\Composite\HashMapType;
use App\Mock\Parameters\Schema\Type\Composite\ObjectType;
use App\Mock\Parameters\Schema\Type\TypeInterface;
use App\OpenAPI\Parsing\SpecificationAccessor;
use App\OpenAPI\Parsing\SpecificationPointer;
use App\OpenAPI\Parsing\Type\Composite\ObjectTypeParser;
use App\Tests\Utility\TestCase\ParsingTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ObjectTypeParserTest extends TestCase
{
    use ParsingTestCaseTrait;

    private const PROPERTY_TYPE = 'propertyType';
    private const PROPERTY_NAME = 'propertyName';
    private const PROPERTY_SCHEMA = [
        'type' => self::PROPERTY_TYPE,
    ];
    private const DEFAULT_PROPERTY_NAME = 'defaultPropertyName';
    private const DEFAULT_PROPERTY_SCHEMA = [
        'type' => 'defaultPropertyType',
    ];
    private const VALID_OBJECT_SCHEMA = [
        'type'       => 'object',
        'properties' => [
            self::PROPERTY_NAME => self::PROPERTY_SCHEMA,
        ],
        'required' => [
            self::PROPERTY_NAME,
        ],
    ];
    private const PROPERTY_POINTER_PATH = ['properties', 'propertyName'];
    private const DEFAULT_PROPERTY_POINTER_PATH = ['properties', 'defaultPropertyName'];
    private const HASH_MAP_SCHEMA = [
        'type'                 => 'object',
        'additionalProperties' => self::PROPERTY_SCHEMA,
    ];
    private const HASH_MAP_SCHEMA_WITH_DEFAULT_PROPERTIES = [
        'type'                 => 'object',
        'additionalProperties' => self::PROPERTY_SCHEMA,
        'properties'           => [
            self::DEFAULT_PROPERTY_NAME => self::DEFAULT_PROPERTY_SCHEMA,
        ],
        'required' => [
            self::DEFAULT_PROPERTY_NAME,
        ],
    ];
    private const HASH_MAP_SCHEMA_WITH_MIN_MAX = [
        'type'                 => 'object',
        'additionalProperties' => self::PROPERTY_SCHEMA,
        'minProperties'        => self::MIN_PROPERTIES,
        'maxProperties'        => self::MAX_PROPERTIES,
    ];
    private const FREE_FORM_SCHEMA = [
        'type'                 => 'object',
        'additionalProperties' => true,
    ];
    private const FREE_FORM_SCHEMA_WITH_MIN_MAX = [
        'type'                 => 'object',
        'additionalProperties' => true,
        'minProperties'        => self::MIN_PROPERTIES,
        'maxProperties'        => self::MAX_PROPERTIES,
    ];
    private const MIN_PROPERTIES = 1;
    private const MAX_PROPERTIES = 2;

    protected function setUp(): void
    {
        $this->setUpParsingContext();
    }

    /** @test */
    public function parsePointedSchema_validSchemaWithProperties_propertiesParsedByTypeParsers(): void
    {
        $parser = $this->createObjectTypeParser();
        $expectedPropertyType = \Phake::mock(TypeInterface::class);
        $this->givenInternalParser_parsePointedSchema_returns($expectedPropertyType);
        $specification = new SpecificationAccessor(self::VALID_OBJECT_SCHEMA);

        /** @var ObjectType $object */
        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInternalParser_parsePointedSchema_wasCalledOnceWithSpecificationAndPointerPath(
            $specification,
            self::PROPERTY_POINTER_PATH
        );
        $this->assertObjectIsValidAndHasProperty($object, $expectedPropertyType);
    }

    /**
     * @test
     * @dataProvider freeFormAdditionalPropertiesProvider
     */
    public function parsePointedSchema_validSchemaWithFreeFormAdditionalProperties_freeFormObjectTypeReturned(
        $additionalProperties
    ): void {
        $parser = $this->createObjectTypeParser();
        $specification = new SpecificationAccessor([
            'type'                 => 'object',
            'additionalProperties' => $additionalProperties,
        ]);

        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(FreeFormObjectType::class, $object);
    }

    public function freeFormAdditionalPropertiesProvider(): array
    {
        return [
            [true],
            [[]],
        ];
    }

    /** @test */
    public function parsePointedSchema_schemaWithFreeFormAndEmptyMinMaxValues_freeFormObjectWithDefaultMinMax(): void
    {
        $parser = $this->createObjectTypeParser();
        $specification = new SpecificationAccessor(self::FREE_FORM_SCHEMA);

        /** @var FreeFormObjectType $object */
        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(FreeFormObjectType::class, $object);
        $this->assertSame(0, $object->minProperties);
        $this->assertSame(0, $object->maxProperties);
    }

    /** @test */
    public function parsePointedSchema_schemaWithFreeFormAndGivenMinMaxValues_freeFormObjectWithExpectedMinMax(): void
    {
        $parser = $this->createObjectTypeParser();
        $specification = new SpecificationAccessor(self::FREE_FORM_SCHEMA_WITH_MIN_MAX);

        /** @var FreeFormObjectType $object */
        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(FreeFormObjectType::class, $object);
        $this->assertSame(self::MIN_PROPERTIES, $object->minProperties);
        $this->assertSame(self::MAX_PROPERTIES, $object->maxProperties);
    }

    /** @test */
    public function parsePointedSchema_validSchemaWithHashMapAdditionalProperties_hashMapTypeReturned(): void
    {
        $parser = $this->createObjectTypeParser();
        $type = $this->givenInternalParser_parsePointedSchema_returnsObject();
        $specification = new SpecificationAccessor(self::HASH_MAP_SCHEMA);

        /** @var HashMapType $object */
        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(HashMapType::class, $object);
        $this->assertInternalParser_parsePointedSchema_wasCalledOnceWithSpecificationAndPointerPath(
            $specification,
            ['additionalProperties']
        );
        $this->assertSame($type, $object->value);
    }

    /** @test */
    public function parsePointedSchema_hashMapSchemaWithEmptyMinMaxValues_hashMapWithDefaultMinMax(): void
    {
        $parser = $this->createObjectTypeParser();
        $this->givenInternalParser_parsePointedSchema_returnsObject();
        $specification = new SpecificationAccessor(self::HASH_MAP_SCHEMA);

        /** @var HashMapType $object */
        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(HashMapType::class, $object);
        $this->assertSame(0, $object->minProperties);
        $this->assertSame(0, $object->maxProperties);
    }

    /** @test */
    public function parsePointedSchema_hashMapSchemaWithGivenMinMaxValues_hashMapWithExpectedMinMax(): void
    {
        $parser = $this->createObjectTypeParser();
        $this->givenInternalParser_parsePointedSchema_returnsObject();
        $specification = new SpecificationAccessor(self::HASH_MAP_SCHEMA_WITH_MIN_MAX);

        /** @var HashMapType $object */
        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(HashMapType::class, $object);
        $this->assertSame(self::MIN_PROPERTIES, $object->minProperties);
        $this->assertSame(self::MAX_PROPERTIES, $object->maxProperties);
    }

    /** @test */
    public function parsePointedSchema_validSchemaWithDefaultProperties_hashMapTypeWithDefaultPropertiesReturned(): void
    {
        $parser = $this->createObjectTypeParser();
        $type = \Phake::mock(TypeInterface::class);
        $this->givenInternalParser_parsePointedSchema_returns($type);
        $specification = new SpecificationAccessor(self::HASH_MAP_SCHEMA_WITH_DEFAULT_PROPERTIES);

        /** @var HashMapType $object */
        $object = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(HashMapType::class, $object);
        $this->assertInternalParser_parsePointedSchema_wasCalledTwiceWithSpecificationAndPointerPaths(
            $specification,
            ['additionalProperties'],
            self::DEFAULT_PROPERTY_POINTER_PATH
        );
        $this->assertSame($type, $object->value);
        $this->assertHashMapHasValidDefaultProperty($object, $type);
    }

    /** @test */
    public function parsePointedSchema_invalidSchemaWithFreeFormAdditionalProperties_errorReportedAndFreeFormCreated(): void
    {
        $parser = $this->createObjectTypeParser();
        $specification = new SpecificationAccessor([
            'type'                 => 'object',
            'additionalProperties' => 'invalid',
        ]);

        /** @var FreeFormObjectType $type */
        $type = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(FreeFormObjectType::class, $type);
        $this->assertParsingErrorHandler_reportError_wasCalledOnceWithMessageAndPointerPath(
            'Invalid value of option "additionalProperties"',
            []
        );
    }

    /** @test */
    public function parsePointedSchema_requiredPropertyDoesNotExist_errorReportedAndPropertyIgnored(): void
    {
        $parser = $this->createObjectTypeParser();
        $this->givenInternalParser_parsePointedSchema_returns(\Phake::mock(TypeInterface::class));
        $specification = new SpecificationAccessor([
            'type'       => 'object',
            'properties' => [
                self::PROPERTY_NAME => self::PROPERTY_SCHEMA,
            ],
            'required' => [
                'not_exist',
            ],
        ]);

        /** @var ObjectType $type */
        $type = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertCount(0, $type->required);
        $this->assertParsingErrorHandler_reportError_wasCalledOnceWithMessageAndPointerPath(
            'Required property "not_exist" does not exist',
            ['required']
        );
    }

    /** @test */
    public function parsePointedSchema_invalidRequiredProperty_errorReportedAndPropertyIgnored(): void
    {
        $parser = $this->createObjectTypeParser();
        $this->givenInternalParser_parsePointedSchema_returns(\Phake::mock(TypeInterface::class));
        $specification = new SpecificationAccessor(['required' => [[]]]);

        /** @var ObjectType $type */
        $type = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertCount(0, $type->required);
        $this->assertParsingErrorHandler_reportError_wasCalledOnceWithMessageAndPointerPath(
            'Invalid required property',
            ['required']
        );
    }

    /** @test */
    public function parsePointedSchema_fixedFieldsSchema_typeWithValidFixedFieldsReturned(): void
    {
        $parser = $this->createObjectTypeParser();
        $this->givenInternalParser_parsePointedSchema_returnsObject();
        $specification = new SpecificationAccessor([
            'nullable'  => true,
            'readOnly'  => true,
            'writeOnly' => true,
        ]);

        /** @var TypeInterface $type */
        $type = $parser->parsePointedSchema($specification, new SpecificationPointer());

        $this->assertTrue($type->isNullable());
        $this->assertTrue($type->isReadOnly());
        $this->assertTrue($type->isWriteOnly());
    }

    private function createObjectTypeParser(): ObjectTypeParser
    {
        return new ObjectTypeParser($this->internalParser, $this->errorHandler);
    }

    private function assertObjectIsValidAndHasProperty(ObjectType $object, TypeInterface $propertyType): void
    {
        $this->assertCount(1, $object->properties);
        $this->assertSame($propertyType, $object->properties->get(self::PROPERTY_NAME));
        $this->assertSame([self::PROPERTY_NAME], $object->properties->keys());
        $this->assertSame([self::PROPERTY_NAME], $object->required->toArray());
    }

    private function assertHashMapHasValidDefaultProperty(HashMapType $type, TypeInterface $propertyType): void
    {
        $this->assertCount(1, $type->properties);
        $this->assertSame($propertyType, $type->properties->get(self::DEFAULT_PROPERTY_NAME));
        $this->assertSame([self::DEFAULT_PROPERTY_NAME], $type->properties->keys());
        $this->assertSame([self::DEFAULT_PROPERTY_NAME], $type->required->toArray());
    }
}
