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

namespace Mageplaza\AjaxLayer\Controller\Search\Result;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Index
 * @package Mageplaza\LayeredNavigation\Controller\Search\Result
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Catalog session
     *
     * @var Session
     */
    protected $_catalogSession;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @type \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @type \Mageplaza\LayeredNavigation\Helper\Data
     */
    protected $_moduleHelper;
    /**
     * @type \Magento\CatalogSearch\Helper\Data
     */
    protected $_helper;
    /**
     * @var QueryFactory
     */
    private $_queryFactory;
    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\CatalogSearch\Helper\Data $helper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Mageplaza\LayeredNavigation\Helper\Data $moduleHelper
     */
    public function __construct(
        Context $context,
        Session $catalogSession,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        \Magento\CatalogSearch\Helper\Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Mageplaza\AjaxLayer\Helper\Data $moduleHelper
    ) {
    
        parent::__construct($context);
        $this->_storeManager   = $storeManager;
        $this->_catalogSession = $catalogSession;
        $this->_queryFactory   = $queryFactory;
        $this->layerResolver   = $layerResolver;
        $this->_jsonHelper     = $jsonHelper;
        $this->_moduleHelper   = $moduleHelper;
        $this->_helper         = $helper;
    }

    /**
     * Display search result
     *
     * @return void
     */
    public function execute()
    {
        $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
        /* @var $query \Magento\Search\Model\Query */
        $query = $this->_queryFactory->get();

        $query->setStoreId($this->_storeManager->getStore()->getId());

        if ($query->getQueryText() != '') {
            if ($this->_helper->isMinQueryLength()) {
                $query->setId(0)->setIsActive(1)->setIsProcessed(1);
            } else {
                $query->saveIncrementalPopularity();

                if ($query->getRedirect()) {
                    $this->getResponse()->setRedirect($query->getRedirect());

                    return;
                }
            }

            $this->_helper->checkNotes();
            $this->_view->loadLayout();

            if ($this->_moduleHelper->isEnabled() && $this->getRequest()->isAjax()) {
                $navigation = $this->_view->getLayout()->getBlock('catalogsearch.leftnav');
                $products   = $this->_view->getLayout()->getBlock('search.result');
                $result     = [
                    'products' => $products->toHtml(),
                    'navigation' => $navigation->toHtml()
                ];
                $this->getResponse()->representJson($this->_jsonHelper->jsonEncode($result));
            } else {
                $this->_view->renderLayout();
            }
        } else {
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
        }
    }
}
