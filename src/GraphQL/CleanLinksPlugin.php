<?php


namespace SilverStripe\NextJS\GraphQL;


use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\GraphQL\Schema\Interfaces\TypePlugin;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\Type;
use TractorCow\Fluent\Model\Locale;

class CleanLinksPlugin implements TypePlugin
{
    const IDENTIFIER = 'cleanLinks';

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
        $clean = preg_replace('#^/|/$#', '', $link);
        return empty($clean) ? '/' : $clean;
    }

}
