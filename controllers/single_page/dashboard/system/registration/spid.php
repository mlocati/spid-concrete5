<?php

namespace Concrete\Package\Spid\Controller\SinglePage\Dashboard\System\Registration;

use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;

defined('C5_EXECUTE') or die('Access Denied.');

class Spid extends DashboardPageController
{
    public function view()
    {
        $destinationPage = null;
        $c = $this->request->getCurrentPage();
        if ($c) {
            $children = $c->getCollectionChildrenArray(true);
            foreach ($children as $childID) {
                $childPage = Page::getByID($childID, 'ACTIVE');
                $ncp = new Checker($childPage);
                if ($ncp->canRead()) {
                    $destinationPage = $childPage;
                    break;
                }
            }
        }
        if ($destinationPage === null) {
            $destinationPage = Page::getByPath('/dashboard');
        }

        return $this->app->make(ResponseFactoryInterface::class)->redirect(
            $this->app->make('url/manager')->resolve([$destinationPage]),
            302
        );
    }
}
