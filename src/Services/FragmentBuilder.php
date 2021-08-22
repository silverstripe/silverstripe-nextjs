<?php


namespace SilverStripe\NextJS\Services;


use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
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
     * @param ObjectType $type
     * @param string | null $parentName
     * @return array|string[]
     * @throws SchemaBuilderException
     */
    private function getFieldsForType(ObjectType $type, ?string $parentName = null): array
    {
        $interfaceName = InterfaceBuilder::interfaceName($type->name, $this->getConfig());
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
            $type = Type::getNamedType($field->getType());

            if (Type::isBuiltInType($type)) {
                $result[$field->name] = true;
                continue;
            }

            try {
                $typeObj = $this->getSchema()->getType($type->name);
                if (!$typeObj instanceof ObjectType) {
                    continue;
                }
            } catch (Exception $e) {
                continue;
            }

            // Block recursion
            if ($typeObj === $type) {
                continue;
            }

            $fields = $typeObj->getFields();
            if (isset($fields['edges']) && isset($fields['nodes'])) {
                $nodeType = Type::getNamedType($fields['nodes']->getType());
                if (!$nodeType instanceof ObjectType || $nodeType === $type) {
                    continue;
                }
                $result[$field->name]['nodes'] = $this->getFieldsForType($nodeType, $field->name);
            } else {
                $result[$field->name] = $this->getFieldsForType($typeObj, $field->name);
            }
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
