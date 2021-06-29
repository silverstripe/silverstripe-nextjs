<?php


namespace SilverStripe\NextJS\GraphQL;


use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Utils\AST;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;

class Resolver
{
    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @return array
     * @throws SchemaBuilderException
     */
    public static function resolveStaticBuild($obj, array $args = [], array $context = []): array
    {
        $result = [];
        $config = SchemaConfigProvider::get($context);
        foreach (SiteTree::get() as $page) {
            $result[] = [
                'link' => $page->Link(),
                'type' => $config->getTypeNameForClass($page->ClassName),
            ];
        }

        return $result;
    }

    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @return array
     * @throws SchemaBuilderException
     */
    public static function resolveTemplateManifest($obj, array $args = [], array $context = []): array
    {
        $templates = $args['templates'];
        $config = SchemaConfigProvider::get($context);
        $typeMapping = $config->get('typeMapping');
        $map = [];

        foreach ($typeMapping as $class => $typeName) {
             $ancestry = array_map(
                 [ClassInfo::class, 'shortName'],
                 array_reverse(ClassInfo::ancestry($class))
             );
             foreach ($ancestry as $candidate) {
                if (in_array($candidate, $templates)) {
                    $map[$typeName] = $candidate;
                    break;
                }
             }
        }

        $result = [];
        foreach ($map as $type => $template) {
            $result[] = [
                'type' => $type,
                'template' => $template,
            ];
        }

        return $result;
    }

}
