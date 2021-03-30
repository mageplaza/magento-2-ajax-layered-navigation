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
 * @copyright   Copyright (c) Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\AjaxLayer\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Mageplaza\Core\Helper\AbstractData;

/**
 * Class Data
 * @package Mageplaza\LayeredNavigation\Helper
 */
class Data extends AbstractData
{
    const CONFIG_MODULE_PATH = 'layered_navigation';
    const FILTER_TYPE_LIST = 'list';

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function ajaxEnabled($storeId = null)
    {
        return $this->getConfigGeneral('ajax_enable', $storeId) && $this->isModuleOutputEnabled();
    }

    /**
     * @param $filters
     *
     * @return mixed
     */
    public function getLayerConfiguration($filters)
    {
        $params       = $this->_getRequest()->getParams();
        $filterParams = [];
        foreach ($params as $key => $param) {
            if ($key === 'amp;dimbaar') {
                continue;
            }
            $filterParams[htmlentities($key)] = htmlentities($param);
        }
        $config = new DataObject([
            'active' => array_keys($filterParams),
            'params' => $filterParams,
            'isCustomerLoggedIn' => $this->objectManager->create(Session::class)->isLoggedIn()
        ]);

        return self::jsonEncode($config->getData());
    }
}
