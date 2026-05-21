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

namespace Mageplaza\AjaxLayer\Test\Unit\Plugin\PageCache;

use Magento\Framework\App\PageCache\IdentifierInterface;
use Magento\Framework\App\Request\Http;
use Mageplaza\AjaxLayer\Plugin\PageCache\IdentifierVaryByAjax;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers the FPC vary-by-ajax fix that prevents AJAX JSON and full HTML pages
 * from colliding on a single full page cache key.
 */
class IdentifierVaryByAjaxTest extends TestCase
{
    private IdentifierVaryByAjax $plugin;

    /** @var Http&MockObject */
    private $request;

    /** @var IdentifierInterface&MockObject */
    private $subject;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Http::class);
        $this->subject = $this->createMock(IdentifierInterface::class);
        $this->plugin = new IdentifierVaryByAjax($this->request);
    }

    public function testAppendsSuffixForAjaxRequest(): void
    {
        $this->request->method('isAjax')->willReturn(true);

        $this->assertSame(
            'cache-key-ajax',
            $this->plugin->afterGetValue($this->subject, 'cache-key')
        );
    }

    public function testKeepsResultForNonAjaxRequest(): void
    {
        $this->request->method('isAjax')->willReturn(false);

        $this->assertSame(
            'cache-key',
            $this->plugin->afterGetValue($this->subject, 'cache-key')
        );
    }

    public function testAppendsSuffixToEmptyResult(): void
    {
        $this->request->method('isAjax')->willReturn(true);

        $this->assertSame('-ajax', $this->plugin->afterGetValue($this->subject, ''));
    }
}
