<?php


namespace SilverStripe\NextJS\Extensions;

use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\NextJS\Services\Configuration;
use SilverStripe\NextJS\Services\PreviewTokenFactory;
use SilverStripe\ORM\DataExtension;

class SiteTreeExtension extends DataExtension
{
    /**
     * @param $link
     */
    public function updatePreviewLink(&$link)
    {
        $relative = $this->owner->RelativeLink();
        $config = Configuration::singleton();
        $baseURL = $config->getBaseURL();
        $previewKey = $config->getPreviewKey();

        if (!$baseURL || !$previewKey) {
            return;
        }

        $factory = PreviewTokenFactory::create($previewKey);
        $link = $config->getPreviewLink(
            $factory->createToken($this->owner),
            $relative
        );
    }

    /**
     * @return string
     */
    public function alternateAbsoluteLink()
    {
        $baseURL = Configuration::singleton()->getBaseURL();
        if (!$baseURL) {
            return $this->owner->Link();
        }

        return sprintf(
            '%s%s',
            $baseURL,
            $this->owner->Link()
        );
    }

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields($fields)
    {
        $config = Configuration::singleton();
        $baseURL = $config->getBaseURL();
        $previewKey = $config->getPreviewKey();
        $endpoint = $config->getPreviewEndpoint();
        if (!$baseURL || !$previewKey) {
            $fields->addFieldToTab(
                'Root.Main',
                LiteralField::create(
                    'NextJSVarsWarning',
                    '<div class="alert alert-warning">' . _t(
                        static::class . '.NEXTJS_ENVVARS_WARNING',
                        'Warning: NextJS environment variables are not set. You must set <strong>NEXTJS_BASE_URL</strong>
                        and <strong>NEXTJS_PREVIEW_KEY</strong> in your environment for headless features to be enabled
                        in the CMS.'
                    ) . '</div>'
                ),
                'Title'
            );
            return;
        }

        $field = $fields->dataFieldByName('URLSegment');
        if (!$field) {
            return;
        }

        $factory = PreviewTokenFactory::create($previewKey);

        /* @var SiteTreeURLSegmentField $field */
        $field
            ->setURLPrefix(sprintf(
                '%s/%s?token=%s&slug=',
                $baseURL,
                $endpoint,
                $factory->createToken($this->owner)
            ))
            ->setURLSuffix(' ');
    }

}
