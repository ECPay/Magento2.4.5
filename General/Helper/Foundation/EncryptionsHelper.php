<?php

namespace Ecpay\General\Helper\Foundation;

use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Framework\App\Helper\AbstractHelper;

class EncryptionsHelper extends AbstractHelper
{
    /**
     * AES 加解密方式
     *
     * @var string
     */
    public $aes128Method = 'AES-128-CBC';

    /**
     * AES 加密選項
     *
     * @var integer
     */
    public $aes128Option = OPENSSL_RAW_DATA;

    /**
     * 編碼 URL 字串
     *
     * @param  string  $data
     * @return string
     */
    public function urlEncode($data)
    {
        $encoded = urlencode($data);

        // 取代為與 .net 相符字元
        $search   = ['%2d', '%5f', '%2e', '%21', '%2a', '%28', '%29'];
        $replace  = ['-', '_', '.', '!', '*', '(', ')'];
        $replaced = str_ireplace($search, $replace, $encoded);

        return $replaced;
    }

    /**
     * 解碼 URL 字串
     *
     * @param  string  $data
     * @return string
     */
    public function urlDecode($data)
    {
        return urldecode($data);
    }

    /**
     * 資料轉 JSON
     *
     * @param  array    $data
     * @param  integer  $options
     * @param  integer  $depth
     * @return json
     */
    public function jsonEncode($data, $options = 0, $depth = 512)
    {
        return json_encode($data, $options, $depth);
    }

    /**
     * JSON 轉陣列
     *
     * @param  string $encoded
     * @return array
     */
    public function jsonDecode($encoded)
    {
        return json_decode($encoded, true);
    }


    /**
     * Base64編碼
     *
     * @param  string $encode
     * @return array
     */
    public function base64Encode($data)
    {
        return base64_encode($data);
    }

    /**
     * Base64解碼
     *
     * @param  string $encoded
     * @return array
     */
    public function base64Decode($encoded)
    {
        return base64_decode($encoded);
    }

    /**
     * AES 加密
     *
     * @param  string $data
     * @param  string $key
     * @param  string $iv
     * @return string
     */
    public function aes128Encrypt($data, $key, $iv)
    {
        $encrypted = openssl_encrypt($data, $this->aes128Method, $key, $this->aes128Option, $iv);
        return $encrypted;
    }

    /**
     * AES 解密
     *
     * @param  string $data
     * @param  string $key
     * @param  string $iv
     * @return string
     */
    public function aes128Decrypt($data, $key, $iv)
    {
        $decrypted = openssl_decrypt($data, $this->aes128Method, $key, $this->aes128Option, $iv);
        return $decrypted;
    }
}