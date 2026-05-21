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

namespace Mageplaza\AjaxLayer\Test\Unit\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Mageplaza\AjaxLayer\Helper\Data;
use Mageplaza\AjaxLayer\Test\Unit\StaticJsonTestCase;
use ReflectionProperty;

/**
 * Covers the configuration gate and the layer configuration payload builder.
 */
class DataTest extends StaticJsonTestCase
{
    public function testAjaxEnabledReturnsTrueWhenConfigAndOutputEnabled(): void
    {
        $helper = $this->createPartialMock(Data::class, ['getConfigGeneral', 'isModuleOutputEnabled']);
        $helper->method('getConfigGeneral')->with('ajax_enable', null)->willReturn('1');
        $helper->method('isModuleOutputEnabled')->willReturn(true);

        $this->assertTrue($helper->ajaxEnabled());
    }

    public function testAjaxEnabledReturnsFalseWhenConfigDisabled(): void
    {
        $helper = $this->createPartialMock(Data::class, ['getConfigGeneral', 'isModuleOutputEnabled']);
        $helper->method('getConfigGeneral')->willReturn('0');
        $helper->method('isModuleOutputEnabled')->willReturn(true);

        $this->assertFalse($helper->ajaxEnabled());
    }

    public function testAjaxEnabledReturnsFalseWhenModuleOutputDisabled(): void
    {
        $helper = $this->createPartialMock(Data::class, ['getConfigGeneral', 'isModuleOutputEnabled']);
        $helper->method('getConfigGeneral')->willReturn('1');
        $helper->method('isModuleOutputEnabled')->willReturn(false);

        $this->assertFalse($helper->ajaxEnabled());
    }

    public function testGetLayerConfigurationEscapesParamsSkipsReservedKeyAndReportsLoginState(): void
    {
        $params = [
            'color'       => '<b>red',
            'amp;dimbaar' => 'should-be-skipped',
            'price'       => '10-20',
        ];

        $request = $this->createMock(RequestInterface::class);
        $request->method('getParams')->willReturn($params);

        $session = $this->createMock(Session::class);
        $session->method('isLoggedIn')->willReturn(true);

        $this->registerJsonObjectManager([Session::class => $session]);

        $helper = $this->createPartialMock(Data::class, ['_getRequest']);
        $helper->method('_getRequest')->willReturn($request);

        $objectManagerProperty = new ReflectionProperty(Data::class, 'objectManager');
        $objectManagerProperty->setValue($helper, $this->getInaccessibleObjectManager());

        $json = $helper->getLayerConfiguration([]);
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertArrayNotHasKey('amp;dimbaar', $data['params']);
        $this->assertSame(htmlentities('<b>red'), $data['params']['color']);
        $this->assertSame('10-20', $data['params']['price']);
        $this->assertContains('color', $data['active']);
        $this->assertContains('price', $data['active']);
        $this->assertNotContains('amp;dimbaar', $data['active']);
        $this->assertTrue($data['isCustomerLoggedIn']);
    }

    /**
     * The helper resolves the customer session through the same global ObjectManager
     * registered for static jsonEncode(); reuse that stub instance here.
     */
    private function getInaccessibleObjectManager(): \Magento\Framework\ObjectManagerInterface
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }
}
