<?php

namespace Ecpay\General\Helper\Services\Common;

use Ecpay\General\Helper\Foundation\EncryptionsHelper;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Framework\App\Helper\AbstractHelper;

class EncryptionsService extends AbstractHelper
{
    /**
     * EncryptionsHelper
     *
     * @var EncryptionsHelper
     */
    protected $_encryptionsHelper ;

    /**
     * GeneralHelper
     *
     * @var GeneralHelper
     */
    protected $_generalHelper ;

    /**
     * @param EncryptionsHelper $encryptionsHelper
     * @param GeneralHelper $generalHelper
     */
	public function __construct(
        EncryptionsHelper $encryptionsHelper,
        GeneralHelper $generalHelper
    ) {
		$this->_encryptionsHelper = $encryptionsHelper;
		$this->_generalHelper = $generalHelper;
	}

    /**
     * 產生 AES KEY、IV
     *
     * @param  string $cipher
     * @param  int    $keylen
     * @param  bool   $ivTag
     * @return array  $aes
     */
    public function aesGenerate(string $cipher, int $keylen = 32, bool $ivTag = true)
    {
        $aes['key'] = $this->_generalHelper->random($keylen);

        if ($ivTag) {
            $ivlen = openssl_cipher_iv_length($cipher);
            $aes['iv'] = $this->_generalHelper->random($ivlen);
        }

        return $aes;
    }

    /**
     * 資料加密
     *
     * @param  string $data
     * @return string $encData
     */
    public function encrypt($data)
    {
        // 取得 KEY、IV
        $aes = $this->_generalHelper->getEncryKeyIV();

        $encData = $this->_encryptionsHelper->urlEncode($data);
        $encData = $this->_encryptionsHelper->aes128Encrypt($encData, $aes['key'], $aes['iv']);
        $encData = $this->_encryptionsHelper->base64Encode($encData);

        return $encData;
    }

    /**
     * 資料解密
     *
     * @param  string $data
     * @return string $decData
     */
    public function decrypt($data)
    {
        // 取得 KEY、IV
        $aes = $this->_generalHelper->getEncryKeyIV();

        $decData = $this->_encryptionsHelper->base64Decode($data);
        $decData = $this->_encryptionsHelper->aes128Decrypt($decData, $aes['key'], $aes['iv']);
        $decData = $this->_encryptionsHelper->urlDecode($decData);

        return $decData;
    }
}