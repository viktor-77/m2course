<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlCache\Controller\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\GraphQl\Controller\HttpRequestProcessor;
use Magento\GraphQlCache\Model\CacheableQuery;
use Magento\GraphQlCache\Model\CacheId\CacheIdCalculator;
use Magento\PageCache\Model\Config;

/**
 * Plugin for handling controller after controller tags and pre-controller validation.
 */
class GraphQl
{
    /**
     * @var CacheableQuery
     */
    private $cacheableQuery;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ResponseHttp
     */
    private $response;

    /**
     * @var HttpRequestProcessor
     */
    private $requestProcessor;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CacheIdCalculator
     */
    private $cacheIdCalculator;

    /**
     * @param CacheableQuery $cacheableQuery
     * @param Config $config
     * @param ResponseHttp $response
     * @param HttpRequestProcessor $requestProcessor
     * @param Registry $registry
     * @param CacheIdCalculator $cacheIdCalculator
     */
    public function __construct(
        CacheableQuery       $cacheableQuery,
        Config               $config,
        ResponseHttp         $response,
        HttpRequestProcessor $requestProcessor,
        Registry             $registry,
        CacheIdCalculator    $cacheIdCalculator
    )
    {
        $this->cacheableQuery = $cacheableQuery;
        $this->config = $config;
        $this->response = $response;
        $this->requestProcessor = $requestProcessor;
        $this->registry = $registry;
        $this->cacheIdCalculator = $cacheIdCalculator;
    }

    /**
     * Process graphql headers
     *
     * @param FrontControllerInterface $subject
     * @param RequestInterface $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface         $request
    )
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $this->requestProcessor->processHeaders($request);
    }

    /**
     * Plugin for GraphQL after render from dispatch to set tag and cache headers
     *
     * @param ResultInterface $subject
     * @param ResultInterface $result
     * @param ResponseHttp $response
     * @return ResultInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)
    {
        $sendNoCacheHeaders = false;
        if ($this->config->isEnabled()) {
            /** @see \Magento\Framework\App\Http::launch */
            /** @see \Magento\PageCache\Model\Controller\Result\BuiltinPlugin::afterRenderResult */
            $this->registry->register('use_page_cache_plugin', true, true);

            $cacheId = $this->cacheIdCalculator->getCacheId();
            if ($cacheId) {
                $this->response->setHeader(CacheIdCalculator::CACHE_ID_HEADER, $cacheId, true);
            }

            if ($this->cacheableQuery->shouldPopulateCacheHeadersWithTags()) {
                $this->response->setPublicHeaders($this->config->getTtl());
                $this->response->setHeader('X-Magento-Tag', implode(',', $this->cacheableQuery->getCacheTags()), true);
            } else {
                $sendNoCacheHeaders = true;
            }
        } else {
            $sendNoCacheHeaders = true;
        }

        if ($sendNoCacheHeaders) {
            $this->response->setNoCacheHeaders();
        }

        return $result;
    }
}
