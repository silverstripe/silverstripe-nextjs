<?php


namespace SilverStripe\NextJS\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\NextJS\Services\Configuration;
use SilverStripe\NextJS\Services\PreviewTokenFactory;
use SilverStripe\Security\Permission;

class PreviewController extends Controller
{
    protected function init()
    {
        parent::init();
        if (!Permission::check('CMS_ACCESS_CMSMain')) {
            return $this->httpError(403);
        }
    }

    public function index(HTTPRequest $request)
    {
        $link = $request->getVar('link');
        $config = Configuration::singleton();
        $previewURL = $config->getPreviewLink(
            PreviewTokenFactory::create($config->getPreviewKey()),
            $link
        );


    }

    /**
     * @param string|null
     */
    public function Link($action = null): string
    {
        return 'nextjs-preview';
    }
}
