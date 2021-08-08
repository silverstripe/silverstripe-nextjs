<?php


namespace SilverStripe\NextJS\Admins;

use SilverStripe\NextJS\Model\LinkIndex;
use SilverStripe\Admin\ModelAdmin;

class LinkIndexAdmin extends ModelAdmin
{
    /**
     * @var string
     */
    private static $menu_title = 'Link index';

    /**
     * @var string
     */
    private static $url_segment = 'linkindexadmin';

    /**
     * @var array
     */
    private static $managed_models = [
        LinkIndex::class,
    ];
}
