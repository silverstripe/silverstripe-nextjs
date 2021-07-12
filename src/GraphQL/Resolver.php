<?php


namespace SilverStripe\NextJS\GraphQL;


use Psr\SimpleCache\CacheInterface;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\Versioned\Versioned;
use Psr\SimpleCache\InvalidArgumentException;
use Exception;

class Resolver
{
    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @return array
     * @throws InvalidArgumentException
     */
    public static function resolveStaticBuild($obj, array $args = [], array $context = []): array
    {
        $cache = self::getCache();
        $result = [];
        $buildID = $args['buildID'];

        if ($cache->has($buildID)) {
            return $cache->get($buildID);
        }

        if(class_exists(Versioned::class)) {
            Versioned::set_stage(Versioned::LIVE);
        }
        foreach (SiteTree::get()->limit(999) as $page) {
            if ($page instanceof ErrorPage) {
                continue;
            }
            if (!$page->URLSegment === 'about-us') {
                continue;
            }
            $result[] = [
                'link' => $page->Link(),
            ];
        }

        $cache->set($buildID, $result);

        return $result;
    }

    /**
     * @param $obj
     * @param array $args
     * @param array $context
     * @return mixed|null
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws SchemaBuilderException
     */
    public static function resolveTemplatesForLinks($obj, array $args, array $context): ?array
    {
        $templates = $args['templates'];
        $buildID = $args['buildID'];
        $config = SchemaConfigProvider::get($context);
        $manifest = self::getTemplateManifest($buildID, $templates, $config);
        $result = [];
        foreach ($args['links'] as $link) {
            $page = SiteTree::get_by_link($link);
            if (!$page) {
                throw new Exception(
                    sprintf('Link %s could not be found', $link)
                );
            }
            $type = $config->getTypeNameForClass($page->ClassName);
            $template = $manifest[$type] ?? null;
            if (!$template) {
                throw new Exception(sprintf(
                    'No template found for %s',
                    $link
                ));
            }
            $result[] = [
                'template' => $template,
                'link' => $link,
            ];
        }

        return $result;
    }

    /**
     * @param string $buildID
     * @param array $templates
     * @param SchemaConfig $config
     * @return array
     * @throws SchemaBuilderException
     * @throws InvalidArgumentException
     */
    private static function getTemplateManifest(string $buildID, array $templates, SchemaConfig $config): array
    {
        /* @var CacheInterface $cache */
        $cache = Injector::inst()->get(CacheInterface::class . '.nextjs');
        $cacheKey = md5($buildID . json_encode($templates));
        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }
        $typeMapping = $config->get('typeMapping');
        $map = [];

        foreach ($typeMapping as $class => $typeName) {
             $ancestry = array_map(function ($ancestor) use ($config) {
                 return $config->getTypeNameForClass($ancestor);
             }, array_reverse(ClassInfo::ancestry($class)));

             foreach ($ancestry as $candidate) {
                if (in_array($candidate, $templates)) {
                    $map[$typeName] = $candidate;
                    break;
                }
             }
        }
        $cache->set($cacheKey, $map);
        return $map;
    }

    /**
     * @return CacheInterface
     */
    private static function getCache(): CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.nextjs');
    }

}
