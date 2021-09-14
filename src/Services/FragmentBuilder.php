<?php


namespace SilverStripe\NextJS\Services;


use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\TypeWithFields;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use GraphQL\Type\Schema as GraphQLSchema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use Exception;

class FragmentBuilder
{
    use Injectable;

    /**
     * @var GraphQLSchema
     */
    private $schema;

    /**
     * @var SchemaConfig
     */
    private $config;

    /**
     * @var string[]
     */
    private $baseFields;

    /**
     * @var int
     */
    private $maxNesting = 2;

    /**
     * FragmentBuilder constructor.
     * @param GraphQLSchema $schema
     * @param SchemaConfig $config
     * @param array $baseFields
     */
    public function __construct(GraphQLSchema $schema, SchemaConfig $config, array $baseFields = [])
    {
        $this->schema = $schema;
        $this->config = $config;
        $this->baseFields = $baseFields;
    }

    /**
     * @param string $class
     * @param null $name
     * @return string|null
     * @throws SchemaBuilderException
     */
    public function getFragmentForClass(string $class, $name = null): ?string
    {
        $typeName = $this->getConfig()->getTypeNameForClass($class);
        if (!$typeName) {
            return null;
        }
        try {
            $type = $this->getSchema()->getType($typeName);
            if (!$type instanceof ObjectType) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
        $fragmentName = $name ?? $typeName . 'Fields';

        $result = $this->getFieldsForType($type);
        if (empty($result)) {
            return null;
        }
        $body = $this->formatResult($result);

        $parts = [
            sprintf('fragment %s on %s {', $fragmentName, $typeName),
            $body,
            '}'
        ];

        return implode("\n", $parts);
    }

    /**
     * @param TypeWithFields $type
     * @param int $level
     * @return array|string[]
     * @throws SchemaBuilderException
     */
    private function getFieldsForType(TypeWithFields $type, int $level = 1): array
    {
        $interfaceName = $type instanceof InterfaceType
            ? $type->name
            : InterfaceBuilder::interfaceName($type->name, $this->getConfig());

        $leafInterfaceName = null;
        try {
            $interface = $this->getSchema()->getType($interfaceName);
            if ($interface instanceof InterfaceType) {
                $leafInterfaceName = $interface->name;
            } else {
                return [];
            }
        } catch (Exception $e) {}

        $result = [];
        foreach ($this->baseFields as $baseField) {
            $result[$baseField] = true;
        }

        $ignoreFields = [];
        $inheritedInterfaces = array_filter(
            $type->getInterfaces(),
            function (InterfaceType $i) use ($leafInterfaceName) {
                return !$leafInterfaceName || $i->name !== $leafInterfaceName;
            }
        );

        foreach ($inheritedInterfaces as $interface) {
            $ignoreFields = array_unique(
                array_merge($ignoreFields, $interface->getFieldNames())
            );
        }

        foreach ($type->getFields() as $field) {
            if (in_array($field->name, $ignoreFields)) {
                continue;
            }
            $nestedTypeObj = Type::getNamedType($field->getType());

            if (Type::isBuiltInType($nestedTypeObj)) {
                $result[$field->name] = true;
                continue;
            }
            if($nestedTypeObj instanceof TypeWithFields) {
                $result[$field->name] = [
                    '__typename ## add your fields below' => true
                ];
                continue;
            }
            if ($nestedTypeObj instanceof EnumType) {
                $result[$field->name] = true;
                continue;
            }


//            try {
//                if (!$nestedTypeObj instanceof TypeWithFields) {
//                    continue;
//                }
//            } catch (Exception $e) {
//                continue;
//            }
//
//            // Block recursion
//            if ($level === $this->getMaxNesting()) {
//                continue;
//            }
//            $nestedFields = $nestedTypeObj->getFields();
//            if (isset($nestedFields['edges']) && isset($nestedFields['nodes'])) {
//                $nodeType = Type::getNamedType($nestedFields['nodes']->getType());
//                if (!$nodeType instanceof TypeWithFields || $nodeType === $type) {
//                    continue;
//                }
//                $nodeFields = $this->getFieldsForType($nodeType, $level + 1);
//                if (!empty($nodeFields)) {
//                    $result[$field->name]['nodes'] = $nodeFields;
//                }
//            } else {
//                $nodeFields = $this->getFieldsForType($nestedTypeObj, $level + 1);
//                if (!empty($nodeFields)) {
//                    $result[$field->name] = $nodeFields;
//                }
//            }
        }

        return $result;
    }

    /**
     * @return GraphQLSchema
     */
    public function getSchema(): GraphQLSchema
    {
        return $this->schema;
    }

    /**
     * @return SchemaConfig
     */
    public function getConfig(): SchemaConfig
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getMaxNesting(): int
    {
        return $this->maxNesting;
    }

    /**
     * @param int $maxNesting
     */
    public function setMaxNesting(int $maxNesting): void
    {
        $this->maxNesting = $maxNesting;
    }


    /**
     * @param array $fields
     * @param int $level
     * @return string
     */
    private function formatResult(array $fields, $level = 1): string
    {
        $tabs = str_repeat("\t", $level);
        $result = '';
        foreach ($fields as $field => $branch) {
            if (!is_array($branch)) {
                $result .= sprintf('%s%s%s', $tabs, $field, "\n");
                continue;
            }
            if (empty($branch)) {
                continue;
            }
            $result .= sprintf(
                '%s%s { %s %s%s}%s',
                $tabs,
                $field,
                "\n",
                $this->formatResult($branch, $level + 1),
                $tabs,
                "\n"
            );
        }

        return $result;
    }
}
