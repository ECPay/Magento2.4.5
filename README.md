Magento2.4 綠界科技金流、物流、電子發票模組
===============

目錄
-----------------
* [支援版本](#支援版本)
* [安裝前置作業](#安裝前置作業)
* [綠界模組安裝步驟](#綠界模組安裝步驟)
    * [解壓縮安裝檔](#解壓縮安裝檔)
    * [模組目錄放置規則](#模組目錄放置規則)
    * [模組啟用指令](#模組啟用指令)
    * [更新指令](#更新指令)
* [啟用模組](#啟用模組)
    *  [主要設定](#主要設定)
    *  [金流模組](#金流模組)
    *  [物流模組](#物流模組)
    *  [發票模組](#發票模組)
* [功能參數設定](#功能參數設定)
* [技術支援](#技術支援)
* [參考資料](#參考資料)

支援版本
-----------------
| Magento |
| :-----: |
|  2.4.3-p3  |

安裝前置作業
-----------------
* 模組安裝前的composer套件安裝

    > 1.ECPAY SDK安裝
    ```
    composer require ecpay/sdk
    ```

    > 2.修正SameSite問題

    安裝套件
    ```
    # 使用 Composer 1 執行以下指令
    composer require veriteworks/cookiefix ^2

    # 使用 Composer 2 執行以下指令
    composer require veriteworks/cookiefix
    ```
    啟用套件
    ```
    php bin/magento module:enable Veriteworks_CookieFix
    php bin/magento setup:upgrade ;
    php bin/magento setup:di:compile ;
    php bin/magento setup:static-content:deploy -f ;
    php bin/magento cache:clean ;
    ```

    * 安裝後至後台調整設定： `STORE` > `Configuration` > `GENERAL` > `Web` > `Default Cookie` `Settings` > `SameSite` > `None`


* Linux 主機例行性工作排程 crontab 設定

    若沒有設定排程，無法使用物流及電子發票自動開立程序。

    > 1.編輯 crontab 內容，請`務必`先將使用者先切到 Magento 站台檔案擁有者，再執行以下指令
    ```
    crontab -e
    ```

    > 2.在 crontab 中選擇以下其中一個指令加入
    ```
    # 不指定 group
    * * * * * php /<站台根目錄>/bin/magento cron:run

    # 指定 group 加入 ecpay
    * * * * * php /<站台根目錄>/bin/magento cron:run --group="ecpay"
    ```

綠界模組安裝步驟
-----------------
#### 解壓縮安裝檔
將下載的檔案解壓縮，完成後請參照下方[模組目錄放置規則](#模組目錄放置規則)，把綠界模組放置對應的網站目錄下，再執行[模組啟用指令](#模組啟用指令)及[更新指令](#更新指令)。

※ 提醒：<br>
* 若存在舊版模組，請先移除並且清除快取再上傳。<br>
* 做完任何設定調整，都需清除快取，才能使用調整後的設定，以下為清除快取的購物車網站路徑：
    ```
    購物車後台 ＞ SYSTEM ＞ Cache Management ＞ Flush Magento Cache
    ```

#### 模組目錄放置規則
* 若您的 Magento 購物車內已存在 `code` 資料夾，請複製 `code` 內的 `Ecpay` 資料夾到 Magento 購物車內的 `code` 資料夾。
* 若您的 Magento 購物車內不存在 `code` 資料夾，請複製 `code` 資料夾到 Magento 購物車的 `app` 資料夾。

#### 模組啟用指令
請先執行 `模組啟用指令` 再執行 [更新指令](#更新指令)。

※ 提醒：使用金流、物流、電子發票任一模組都需啟用 `Ecpay_General` (主要設定模組)。
```
php bin/magento module:enable Ecpay_General

php bin/magento module:enable Ecpay_ApplepayPaymentGateway
php bin/magento module:enable Ecpay_AtmPaymentGateway
php bin/magento module:enable Ecpay_BarcodePaymentGateway
php bin/magento module:enable Ecpay_CreditInstallmentPaymentGateway
php bin/magento module:enable Ecpay_CreditPaymentGateway
php bin/magento module:enable Ecpay_CvsPaymentGateway
php bin/magento module:enable Ecpay_WebatmPaymentGateway

php bin/magento module:enable Ecpay_LogisticCsvFamily
php bin/magento module:enable Ecpay_LogisticCsvHilife
php bin/magento module:enable Ecpay_LogisticCsvOkmart
php bin/magento module:enable Ecpay_LogisticCsvUnimart
php bin/magento module:enable Ecpay_LogisticHomePost
php bin/magento module:enable Ecpay_LogisticHomeTcat

php bin/magento module:enable Ecpay_Invoice
```

#### 更新指令
請按順序執行以下指令。
```
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:clean ;  
```
 * 執行後請確認檔案權限為Magento站台檔案擁有者。

啟用模組
-----------------
#### 主要設定
模組總開關：您可在此將需要的綠界服務改為 `啟用`。
> * `購物車後台` ＞ `STORES` ＞ `Configuration` ＞ `ECPAY(綠界科技)` ＞ `主要設定`

#### 金流模組

> * 啟用 `主要設定` 中的 `金流`
> * 啟用欲使用的金流：`購物車後台` ＞ `STORES` ＞ `Configuration` ＞ `SALES` ＞ `Payment Methods` ＞ `OTHER PAYMENT METHODS` ＞ `欲使用的金流`

#### 物流模組

> * 啟用 `主要設定` 中的 `物流`
> * 啟用欲使用的物流：`購物車後台` ＞ `STORES` ＞ `Configuration` ＞ `SALES` ＞ `Shipping Methods` ＞ `欲使用的物流`

#### 發票模組

> * 啟用 `主要設定` 中的 `電子發票`

功能參數設定
-----------------
#### 金流模組
* 金流共用設定：`購物車後台` ＞ `STORES` ＞ `Configuration` ＞ `ECPAY(綠界科技)` ＞ `金流設定`。
    1. 訂單編號前綴：綠界訂單編號長度限制20，超過會自動截斷。
    2. 綠界訂單顯示商品名稱：關閉時，商品名稱固定帶入 `網路商品一批`。
    3. 啟用測試模式：啟用測試模式時，商店代號、金鑰、向量無須填寫。
    4. 商店代號(Merchant ID)
    5. 金鑰(Hash Key)
    6. 向量(Hash IV)

    設定完成後請點選 `SaveConfig` 儲存。

* 金流個別設定：`購物車後台` ＞ `STORES` ＞ `Configuration` ＞ `SALES` ＞ `Payment Methods` ＞ `OTHER PAYMENT METHODS`，可設定最低最高訂單交易門檻等。

#### 物流模組
* 物流共用設定：`購物車後台` ＞ `STORES` ＞ `Configuration` ＞ `ECPAY(綠界科技)` ＞ `物流設定`。
    1. 訂單編號前綴：綠界訂單編號長度限制20，超過會自動截斷。
    2. 綠界訂單顯示商品名稱：關閉時，商品名稱固定帶入 `網路商品一批`。
    3. 自動建立物流訂單：訂單建立成功時自動建立綠界物流訂單。
    4. 寄件人姓名
    5. 寄件人電話
    6. 寄件人手機
    7. 寄件人郵遞區號
    8. 寄件人地址
    9. 啟用測試模式：啟用測試模式時，商店代號、金鑰、向量無須填寫。
    10. 商店代號(Merchant ID)
    11. 金鑰(Hash Key)
    12. 向量(Hash IV)

    設定完成後請點選 `SaveConfig` 儲存。

* 物流個別設定：進入 `購物車後台` ＞ `SALES` ＞ `Configuration` ＞ `SALES` ＞ `Shipping Methods`，可設定物流個別的運費、免費運送門檻等。

* 後台訂單相關操作：進入 `購物車後台` ＞ `SALES` ＞ `Orders` ＞ `訂單詳細頁面` 可執行以下動作。
    1. 變更門市
    2. 建立物流訂單(手動模式)
    3. 列印物流訂單

#### 發票模組
* 發票共用設定：`購物車後台` ＞ `STORES` ＞ `Configuration` ＞ `ECPAY(綠界科技)` ＞ `電子發票設定`。
    1. 訂單編號前綴：綠界訂單編號長度限制30，超過會自動截斷。
    2. 開立發票模式：自動開立模式，會在訂單狀態為processing時自動執行。
    3. 延期開立天數：設定延遲開立天數，發票開立模式為 `延遲開立`。
    4. 預設捐贈單位：結帳頁面發票選擇 `捐贈` 時自動帶入。
    5. 啟用測試模式：啟用測試模式時，商店代號、金鑰、向量無須填寫。
    6. 商店代號(Merchant ID)
    7. 金鑰(Hash Key)
    8. 向量(Hash IV)

    設定完成後請點選 `SaveConfig` 儲存。

* 後台訂單相關操作：進入 `購物車後台` ＞ `SALES` ＞ `Orders` ＞ `訂單詳細頁面` 可執行以下動作。
    1. 開立發票(手動模式)
    2. 作廢發票(手動模式)

注意事項
-----------------
* 本模組不支援後台訂單 Reorder 功能

技術支援
-----------------
綠界技術服務工程師信箱: techsupport@ecpay.com.tw

參考資料
-----------------
#### 金流
* [綠界科技全方位金流API技術文件](https://developers.ecpay.com.tw/?p=2509)

#### 物流
* [綠界科技全方位物流服務API介接技術文件](https://developers.ecpay.com.tw/?p=10075)

#### 發票
* [綠界科技B2C電子發票介接技術文件](https://developers.ecpay.com.tw/?p=7809)
