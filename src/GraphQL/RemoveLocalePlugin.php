<?php


namespace SilverStripe\NextJS\GraphQL;


use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\GraphQL\Schema\Interfaces\TypePlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use TractorCow\Fluent\Model\Locale;

class RemoveLocalePlugin implements TypePlugin
{
    const IDENTIFIER = 'removeLocale';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function apply(Type $type, Schema $schema, array $config = []): void
    {
        if ($field = $type->getFieldByName('link')) {
            $field->addResolverAfterware([static::class, 'sanitise']);
        }
    }

    /**
     * @param string $link
     * @return string
     */
    public static function sanitise(string $link): string
    {
        if (class_exists(Locale::class)) {
            $locale = Locale::getCurrentLocale();
            $base = Controller::join_links(Director::baseURL(), $locale->getURLSegment());

            $clean = preg_replace('#^' . preg_quote($base) . '/#', '', $link);
            return Controller::join_links(Director::baseURL(), $clean);
        }

        return $link;
    }
}
