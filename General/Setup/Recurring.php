<?php

namespace Ecpay\General\Setup;

use Ecpay\General\Helper\Foundation\EncryptionsHelper;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Recurring implements InstallSchemaInterface
{
    /**
     * Writer
     *
     * @var Writer
     */
    protected $_deploymentConfigWriter;

    /**
     * EncryptionsHelper
     *
     * @var EncryptionsHelper
     */
    protected $_encryptionsHelper;

    /**
     * GeneralHelper
     *
     * @var GeneralHelper
     */
    protected $_generalHelper;

    /**
     * EncryptionsService
     *
     * @var EncryptionsService
     */
    protected $_encryptionsService;

    /**
     * @param Writer $deploymentConfigWriter
     * @param EncryptionsHelper $encryptionsHelper
     * @param GeneralHelper $generalHelper
     * @param EncryptionsService $encryptionsService
     */
	public function __construct(
		Writer $deploymentConfigWriter,
		EncryptionsHelper $encryptionsHelper,
		GeneralHelper $generalHelper,
		EncryptionsService $encryptionsService
	) {
		$this->_deploymentConfigWriter = $deploymentConfigWriter;
        $this->_encryptionsHelper = $encryptionsHelper;
        $this->_generalHelper = $generalHelper;
		$this->_encryptionsService = $encryptionsService;
	}

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$hashData = $this->_generalHelper->getEncryKeyIV();

        // 判斷是否存在 Key、IV
        if (empty($hashData['key']) || empty($hashData['iv'])) {
            // 產 Key、IV
            $aes = $this->_encryptionsService->aesGenerate($this->_encryptionsHelper->aes128Method);
            $data = [
                'ecpay' => [
                    'general' => [
                        'hash_key' => $aes['key'],
                        'hash_iv'  => $aes['iv'],
                    ]
                ]
            ];

            // 存入 app/etc/env.php
		    $this->_deploymentConfigWriter->saveConfig([ConfigFilePool::APP_ENV => $data]);
        }
	}
}