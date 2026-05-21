<?php

declare(strict_types=1);

namespace Mageplaza\AjaxLayer\Plugin\PageCache;

use Magento\Framework\App\PageCache\IdentifierInterface;
use Magento\Framework\App\Request\Http;

class IdentifierVaryByAjax
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @param Http $request
     */
    public function __construct(Http $request)
    {
        $this->request = $request;
    }

    public function afterGetValue(IdentifierInterface $subject, string $result): string
    {
        // AjaxLayer returns JSON for isAjax() requests on the same URL as the full HTML page;
        // without this suffix both share one FPC key and the JSON leaks into normal page loads.
        if ($this->request->isAjax()) {
            return $result . '-ajax';
        }

        return $result;
    }
}
