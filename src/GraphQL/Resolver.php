<?php


namespace SilverStripe\NextJS\GraphQL;


use SilversStripe\NextJS\Model\StaticBuild;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceChain;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Exception;

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
        Director::config()->set('alternate_base_url', '');
        $result = [
            'typeAncestry' => [],
            'links' => []
        ];
        $config = SchemaConfigProvider::get($context);
        $typeMapping = $config->get('typeMapping');

        foreach ($typeMapping as $class => $typeName) {
            $ancestralModels = InheritanceChain::create($class)
                ->getAncestralModels();
            $classes = array_filter(
                $ancestralModels,
                function ($ancestor) use ($class) {
                    return !in_array($ancestor, [DataObject::class, $class]);
                }
            );
            $ancestry = array_map(function ($ancestor) use ($config) {
                return $config->getTypeNameForClass($ancestor);
            }, array_reverse($classes));
            $result['typeAncestry'][] = [
                'type' => $config->getTypeNameForClass($class),
                'ancestry' => array_reverse($ancestry),
            ];
        }


        if(class_exists(Versioned::class)) {
            Versioned::set_stage(Versioned::LIVE);
        }

        $pages = StaticBuild::currentBuild()->getCollection();
        foreach ($pages as $page) {
            $result['links'][] = [
                'link' => $page->CleanLink,
            ];
        }

        return $result;
    }

    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @return array
     * @throws Exception
     * @throws SchemaBuilderException
     */
    public static function resolveTypesForLinks($obj, array $args, array $context): array
    {
        $links = $args['links'];
        $config = SchemaConfigProvider::get($context);
        $result = [];
        foreach ($links as $link) {
            $page = SiteTree::get_by_link($link);
            if (!$page) {
                throw new Exception(
                    sprintf('Link %s could not be found', $link)
                );
            }
            $type = $config->getTypeNameForClass($page->ClassName);
            if (!$type) {
                throw new Exception(sprintf(
                    'No type found for %s',
                    $page->ClassName
                ));
            }
            $result[] = [
                'type' => $type,
                'link' => $link,
            ];
        }

        return $result;
    }

}
