<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_AjaxLayer
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

declare(strict_types=1);

namespace Mageplaza\AjaxLayer\Test\Unit\Controller\Search\Result;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\AjaxLayer\Controller\Search\Result\Index;
use Mageplaza\AjaxLayer\Helper\Data as ModuleHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers the AJAX search result controller branching: empty query redirect,
 * AJAX JSON output and the standard full layout render.
 */
class IndexTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private $request;

    /** @var HttpResponse&MockObject */
    private $response;

    /** @var ViewInterface&MockObject */
    private $view;

    /** @var RedirectInterface&MockObject */
    private $redirect;

    /** @var QueryFactory&MockObject */
    private $queryFactory;

    /** @var Query&MockObject */
    private $query;

    /** @var string|null Value returned by the magic Query::getRedirect() getter. */
    private $queryRedirect = null;

    /** @var Resolver&MockObject */
    private $layerResolver;

    /** @var StoreManagerInterface&MockObject */
    private $storeManager;

    /** @var CatalogSearchHelper&MockObject */
    private $searchHelper;

    /** @var JsonHelper&MockObject */
    private $jsonHelper;

    /** @var ModuleHelper&MockObject */
    private $moduleHelper;

    private Index $controller;

    protected function setUp(): void
    {
        $this->request  = $this->createMock(HttpRequest::class);
        $this->response = $this->createMock(HttpResponse::class);
        $this->view     = $this->createMock(ViewInterface::class);
        $this->redirect = $this->createMock(RedirectInterface::class);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getResponse')->willReturn($this->response);
        $context->method('getView')->willReturn($this->view);
        $context->method('getRedirect')->willReturn($this->redirect);
        $context->method('getObjectManager')
            ->willReturn($this->createMock(\Magento\Framework\ObjectManagerInterface::class));
        $context->method('getEventManager')->willReturn($this->createMock(EventManager::class));
        $context->method('getUrl')->willReturn($this->createMock(UrlInterface::class));
        $context->method('getActionFlag')->willReturn($this->createMock(ActionFlag::class));
        $context->method('getMessageManager')->willReturn($this->createMock(MessageManager::class));

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($store);

        // setIsActive(), setIsProcessed() and getRedirect() are magic data accessors on Query,
        // so they are routed through __call() to stay compatible across PHPUnit versions
        // (PHPUnit 12 removed MockBuilder::addMethods()).
        $this->query = $this->createMock(Query::class);
        $this->query->method('setStoreId')->willReturnSelf();
        $this->query->method('setId')->willReturnSelf();
        $this->query->method('__call')->willReturnCallback(
            fn (string $method) => match ($method) {
                'setIsActive', 'setIsProcessed' => $this->query,
                'getRedirect' => $this->queryRedirect,
                default => null,
            }
        );

        $this->queryFactory = $this->createMock(QueryFactory::class);
        $this->queryFactory->method('get')->willReturn($this->query);

        $this->layerResolver = $this->createMock(Resolver::class);
        $this->searchHelper  = $this->createMock(CatalogSearchHelper::class);
        $this->jsonHelper    = $this->createMock(JsonHelper::class);
        $this->moduleHelper  = $this->createMock(ModuleHelper::class);

        $this->controller = new Index(
            $context,
            $this->createMock(CatalogSession::class),
            $this->storeManager,
            $this->queryFactory,
            $this->layerResolver,
            $this->searchHelper,
            $this->jsonHelper,
            $this->moduleHelper
        );
    }

    public function testRedirectsWhenQueryTextIsEmpty(): void
    {
        $this->query->method('getQueryText')->willReturn('');
        $this->redirect->method('getRedirectUrl')->willReturn('https://shop.test/');

        $this->layerResolver->expects($this->once())
            ->method('create')
            ->with(Resolver::CATALOG_LAYER_SEARCH);
        $this->view->expects($this->never())->method('loadLayout');
        $this->response->expects($this->once())
            ->method('setRedirect')
            ->with('https://shop.test/');

        $this->controller->execute();
    }

    public function testRedirectsToQueryRedirectWhenNotMinLength(): void
    {
        $this->query->method('getQueryText')->willReturn('tv');
        $this->searchHelper->method('isMinQueryLength')->willReturn(false);
        $this->query->expects($this->once())->method('saveIncrementalPopularity');
        $this->queryRedirect = 'https://shop.test/redirect';

        $this->view->expects($this->never())->method('loadLayout');
        $this->response->expects($this->once())
            ->method('setRedirect')
            ->with('https://shop.test/redirect');

        $this->controller->execute();
    }

    public function testRepresentsJsonOnAjaxRequest(): void
    {
        $this->query->method('getQueryText')->willReturn('shirt');
        $this->searchHelper->method('isMinQueryLength')->willReturn(true);

        $this->view->expects($this->once())->method('loadLayout');
        $this->view->expects($this->never())->method('renderLayout');

        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getBlock')->willReturnMap([
            ['catalogsearch.leftnav', $this->blockReturning('NAVIGATION_HTML')],
            ['search.result', $this->blockReturning('PRODUCTS_HTML')],
        ]);
        $this->view->method('getLayout')->willReturn($layout);

        $this->moduleHelper->method('ajaxEnabled')->willReturn(true);
        $this->request->method('isAjax')->willReturn(true);

        $this->jsonHelper->expects($this->once())
            ->method('jsonEncode')
            ->with([
                'products'   => 'PRODUCTS_HTML',
                'navigation' => 'NAVIGATION_HTML',
            ])
            ->willReturn('{"json":true}');

        $this->response->expects($this->once())
            ->method('representJson')
            ->with('{"json":true}');

        $this->controller->execute();
    }

    public function testRendersFullLayoutWhenNotAjax(): void
    {
        $this->query->method('getQueryText')->willReturn('shirt');
        $this->searchHelper->method('isMinQueryLength')->willReturn(true);

        $this->view->expects($this->once())->method('loadLayout');
        $this->view->expects($this->once())->method('renderLayout');

        $this->moduleHelper->method('ajaxEnabled')->willReturn(false);

        $this->response->expects($this->never())->method('representJson');

        $this->controller->execute();
    }

    private function blockReturning(string $html): AbstractBlock
    {
        $block = $this->createMock(AbstractBlock::class);
        $block->method('toHtml')->willReturn($html);

        return $block;
    }
}
