<?php

namespace extend\api;

use extend\lib\XibeiApiClient;

/**
 * 主数据接口
 */
class Mdc
{
    const BRAND_LIST_URL = '/master/mdc/getBrandList';
    const STORE_LIST_URL = '/master/mdc/store';
    const GROUP_LIST_URL = '/master/mdc/getDeptParentTree';
    const APP_LIST_URL = '/master/mdc/getAuthList';
    const EMPLPYEE_INFO_URL = '/master/mdc/employeeDetail';

    protected $apiUrlPrefix;
    public $errCode = '';
    public $errMsg = '';

    public function __construct()
    {
        $this->apiUrlPrefix = config('api.mdc_base_uri');
    }

    /**
     * 获取品牌列表
     *
     * @param array $params
     * @return bool
     */
    public function getBrandList($params = [])
    {
        $requestUrl = $this->apiUrlPrefix . self::BRAND_LIST_URL;

        return $this->requestExecute($requestUrl, $params);
    }

    /**
     * 获取分组支部列表
     */
    public function getGroupList($params = [])
    {
        $requestUrl = $this->apiUrlPrefix . self::GROUP_LIST_URL;

        return $this->requestExecute($requestUrl, $params);
    }

    /**
     * 获取门店列表
     */
    public function getStoreList($params = [])
    {
        $requestUrl = $this->apiUrlPrefix . self::STORE_LIST_URL;

        return $this->requestExecute($requestUrl, $params);
    }

    /**
     * 获取授权列表
     */
    public function getAppList()
    {
        $requestUrl = $this->apiUrlPrefix . self::APP_LIST_URL;

        return $this->requestExecute($requestUrl);
    }

    /**
     * 获取员工信息
     */
    public function getEmployeeInfo($params)
    {
        $requestUrl = $this->apiUrlPrefix . self::EMPLPYEE_INFO_URL;

        return $this->requestExecute($requestUrl, $params);
    }

    /**
     * 执行发送请求
     *
     * @param string $url
     * @param array  $data
     * @return bool|array
     */
    public function requestExecute($url, $data = [])
    {
        $client                = new XibeiApiClient();
        $client->appId         = config('app.app_id');
        $client->appKey        = config('app.app_key');
        $client->rsaPrivateKey = config('app.app_rsa_private_key');
        $client->encryptType   = 'AES';
        $client->signType      = 'RSA';
        $client->logCallback   = [$this, 'logCommunicationError'];

        $params = [];
        if (!empty($data)) {
            $params['biz_content'] = json_encode($data);
        }

        $result = $client->execute($url, $params);
        if ($result === false) {
            $this->errCode = -1;
            $this->errMsg  = 'HTTP_ERROR';

            return false;
        }

        if ($result['code'] != 0) {
            $this->errCode = $result['code'];
            $this->errMsg  = $result['msg'];
            $this->logCommunicationError($url, $params, 'BIZ_ERROR_' . $result['code'], json_encode($result));

            return false;
        }

        return $result['data'];
    }

    /**
     * 错误日志记录
     *
     * @param string $requestUrl
     * @param array  $requestData
     * @param string $errorCode
     * @param string $responseTxt
     */
    public function logCommunicationError($requestUrl, $requestData, $errorCode, $responseTxt)
    {
        save_log([
            'request_url'      => $requestUrl,
            'request_data'     => $requestData,
            'error_code'       => $errorCode,
            'response_content' => str_replace("\n", "", $responseTxt)
        ], 1, '接口请求日志', 'inside-api/mdc');
    }
}