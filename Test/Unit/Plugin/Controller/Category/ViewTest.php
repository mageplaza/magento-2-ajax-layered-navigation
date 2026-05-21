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

namespace Mageplaza\AjaxLayer\Test\Unit\Plugin\Controller\Category;

use Magento\Catalog\Controller\Category\View as CategoryViewAction;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Result\Page;
use Mageplaza\AjaxLayer\Helper\Data as LayerData;
use Mageplaza\AjaxLayer\Plugin\Controller\Category\View;
use Mageplaza\AjaxLayer\Test\Unit\StaticJsonTestCase;

/**
 * Covers the category controller plugin that converts a category view into a
 * JSON payload of the product list and layered navigation on AJAX requests.
 */
class ViewTest extends StaticJsonTestCase
{
    /** @var Manager&\PHPUnit\Framework\MockObject\MockObject */
    private $moduleManager;

    /** @var LayerData&\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    private View $plugin;

    protected function setUp(): void
    {
        $this->moduleManager = $this->createMock(Manager::class);
        $this->helper        = $this->createMock(LayerData::class);
        $this->plugin        = new View($this->moduleManager, $this->helper);
    }

    public function testReturnsPageUnchangedWhenAjaxDisabled(): void
    {
        $this->helper->method('ajaxEnabled')->willReturn(false);

        $action = $this->createMock(CategoryViewAction::class);
        $page   = $this->createMock(Page::class);

        $this->assertSame($page, $this->plugin->afterExecute($action, $page));
    }

    public function testReturnsPageUnchangedWhenRequestNotAjax(): void
    {
        $this->helper->method('ajaxEnabled')->willReturn(true);

        $request = $this->createMock(HttpRequest::class);
        $request->method('isAjax')->willReturn(false);

        $action = $this->createMock(CategoryViewAction::class);
        $action->method('getRequest')->willReturn($request);
        $page = $this->createMock(Page::class);

        $this->assertSame($page, $this->plugin->afterExecute($action, $page));
    }

    public function testRepresentsJsonWithProductsAndNavigationOnAjax(): void
    {
        $this->registerJsonObjectManager();

        $this->helper->method('ajaxEnabled')->willReturn(true);
        $this->helper->method('getConfigValue')
            ->with('mpquickview/general/enabled')
            ->willReturn(false);

        $request = $this->createMock(HttpRequest::class);
        $request->method('isAjax')->willReturn(true);

        $layout = $this->createMock(Layout::class);
        $layout->method('getBlock')->willReturnMap([
            ['catalog.leftnav', $this->blockReturning('NAVIGATION_HTML')],
            ['category.products', $this->blockReturning('PRODUCTS_HTML')],
        ]);

        $page = $this->createMock(Page::class);
        $page->method('getLayout')->willReturn($layout);

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->once())
            ->method('representJson')
            ->with($this->callback(static function ($json): bool {
                $decoded = json_decode((string)$json, true);

                return is_array($decoded)
                    && ($decoded['products'] ?? null) === 'PRODUCTS_HTML'
                    && ($decoded['navigation'] ?? null) === 'NAVIGATION_HTML'
                    && !array_key_exists('quickview', $decoded);
            }));

        $action = $this->createMock(CategoryViewAction::class);
        $action->method('getRequest')->willReturn($request);
        $action->method('getResponse')->willReturn($response);

        $this->assertNull($this->plugin->afterExecute($action, $page));
    }

    public function testIncludesQuickViewBlockWhenQuickViewEnabled(): void
    {
        $this->registerJsonObjectManager();

        $this->helper->method('ajaxEnabled')->willReturn(true);
        $this->helper->method('getConfigValue')
            ->with('mpquickview/general/enabled')
            ->willReturn(true);

        $request = $this->createMock(HttpRequest::class);
        $request->method('isAjax')->willReturn(true);

        $layout = $this->createMock(Layout::class);
        $layout->method('getBlock')->willReturnMap([
            ['catalog.leftnav', $this->blockReturning('NAVIGATION_HTML')],
            ['category.products', $this->blockReturning('PRODUCTS_HTML')],
            ['mpquickview.quickview', $this->blockReturning('QUICKVIEW_HTML')],
        ]);

        $page = $this->createMock(Page::class);
        $page->method('getLayout')->willReturn($layout);

        $response = $this->createMock(HttpResponse::class);
        $response->expects($this->once())
            ->method('representJson')
            ->with($this->callback(static function ($json): bool {
                $decoded = json_decode((string)$json, true);

                return is_array($decoded) && ($decoded['quickview'] ?? null) === 'QUICKVIEW_HTML';
            }));

        $action = $this->createMock(CategoryViewAction::class);
        $action->method('getRequest')->willReturn($request);
        $action->method('getResponse')->willReturn($response);

        $this->plugin->afterExecute($action, $page);
    }

    private function blockReturning(string $html): AbstractBlock
    {
        $block = $this->createMock(AbstractBlock::class);
        $block->method('toHtml')->willReturn($html);

        return $block;
    }
}
