<?php
/**
 * BIG FISH Payment Services Ltd.
 * https://paymentgateway.hu
 *
 * @title      BIG FISH Payment Gateway module for Magento 2
 * @category   BigFish
 * @package    Bigfishpaymentgateway_Pmgw
 * @author     BIG FISH Payment Services Ltd., it [at] paymentgateway [dot] hu
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright  Copyright (c) 2024, BIG FISH Payment Services Ltd.
 */

namespace Bigfishpaymentgateway\Pmgw\Gateway\Request;

use BigFish\PaymentGateway\Data\Info;
use BigFish\PaymentGateway\Request\Init;
use BigFish\PaymentGateway\Request\GetPaymentRegistrations as GetPaymentRegistrationsRequest;
use Bigfishpaymentgateway\Pmgw\Model\ConfigProvider;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use BigFish\PaymentGateway;
use BigFish\PaymentGateway\Config;
use BigFish\PaymentGateway\Request\Init as InitRequest;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Webapi\Controller\Rest\InputParamsResolver;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class InitializeRequest implements BuilderInterface
{
    const MAX_NAME_LENGTH = 45;
    const MAX_EMAIL_LENGTH = 254;
    const MAX_PHONE_LENGTH = 18;
    const MAX_POSTAL_CODE_LENGTH = 16;
    const MAX_CITY_LENGTH = 50;
    const MAX_ADDRESS_LINE_LENGTH = 50;
    const MAX_COUNTRY_LENGTH = 50;
    const MAX_COUNTRY_CODE_2_LENGTH = 2;

    const MB_DEFAULT_ENCODING = 'UTF-8';

    /**
     * @var ConfigProvider
     */
    private $providerConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetaData;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PaymentInterface
     */
    private $paymentMethod;

    /**
     * @var InputParamsResolver
     */
    private $inputParamsResolver;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param ConfigProvider $providerConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     * @param Helper $helper
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentInterface $paymentMethod
     * @param CustomerRepositoryInterface $customerRepository
     * @param InputParamsResolver $inputParamsResolver
     */
    public function __construct(
        ConfigProvider $providerConfig,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        Helper $helper,
        LoggerInterface $logger,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        PaymentInterface $paymentMethod,
        CustomerRepositoryInterface $customerRepository,
        InputParamsResolver $inputParamsResolver
    ) {
        $this->providerConfig = $providerConfig;
        $this->storeManager = $storeManager;
        $this->productMetaData = $productMetadata;
        $this->moduleList = $moduleList;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
        $this->paymentMethod = $paymentMethod;
        $this->inputParamsResolver = $inputParamsResolver;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws \Exception
     */
    public function build(array $buildSubject)
    {
        try {
            if (
                !isset($buildSubject['payment']) ||
                !$buildSubject['payment'] instanceof PaymentDataObjectInterface
            ) {
                throw new \InvalidArgumentException('Payment data object should be provided');
            }

            /** @var PaymentDataObjectInterface $payment */
            $payment = $buildSubject['payment'];

            /** @var OrderAdapterInterface $order */
            $order = $payment->getOrder();

            $providerConfig = $this->getProviderConfig($payment);

            if (empty($providerConfig)) {
                throw new \UnexpectedValueException('Payment parameter array should be provided');
            }

            $this->helper->setPaymentGatewayConfig(
                $this->getPaymentGatewayConfig($providerConfig)
            );

            $response = $this->helper->initializePaymentGatewayTransaction(
                $this->getPaymentGatewayInitRequest($order, $providerConfig)
            );

            if ($response->ResultCode === PaymentGateway::RESULT_CODE_SUCCESS) {
                $transaction = $this->helper->createTransaction();

                $transaction
                    ->setOrderId($order->getOrderIncrementId())
                    ->setTransactionId($response->TransactionId)
                    ->setCreatedTime($this->dateTime->date())
                    ->setStatus(Helper::TRANSACTION_STATUS_INITIALIZED)
                    ->save();

                $this->helper->addTransactionLog($transaction, $response->getData());
            } else {
                $message = $response->ResultCode . ': ' . $response->ResultMessage;
                $this->logger->critical($message);
                throw new \UnexpectedValueException($message);
            }

            return $response->getData();
        } catch (\Exception $exception) {
            throw new \Magento\Framework\Exception\LocalizedException(__($exception->getMessage()), $exception);
        }
    }

    /**
     * @param PaymentDataObjectInterface $payment
     * @return array
     * @throws LocalizedException
     */
    protected function getProviderConfig(PaymentDataObjectInterface $payment)
    {
        $methodCode = $payment->getPayment()->getMethodInstance()->getCode();

        return $this->providerConfig->getProviderConfig($methodCode);
    }

    /**
     * @param array $providerConfig
     * @return Config
     */
    protected function getPaymentGatewayConfig(array $providerConfig)
    {
        $config = new Config();

        $config->storeName = $providerConfig['storename'];
        $config->apiKey = $providerConfig['apikey'];
        $config->testMode = ((int)$providerConfig['testmode'] === 1);

        return $config;
    }

    /**
     * @param OrderAdapterInterface $order
     * @param $providerConfig
     * @return InitRequest
     * @throws \Exception
     */
    protected function getPaymentGatewayInitRequest(OrderAdapterInterface $order, array $providerConfig)
    {
        $request = new InitRequest();

        $request
            ->setProviderName($providerConfig['provider_code'])
            ->setResponseUrl($this->getStoreBaseUrl() . $providerConfig['response_url'])
            ->setAmount($order->getGrandTotalAmount())
            ->setCurrency($order->getCurrencyCode())
            ->setOrderId($order->getOrderIncrementId())
            ->setUserId((string)$order->getCustomerId())
            ->setLanguage($this->getStoreLanguage())
            ->setModuleName('Magento (' . $this->productMetaData->getVersion() . ')')
            ->setModuleVersion($this->moduleList->getOne(Helper::MODULE_NAME)['setup_version'])
            ->setAutoCommit(true);

        $extraData = [];

        if ($providerConfig['name'] == ConfigProvider::CODE_KHB_SZEP) {
            $extraData['KhbCardPocketId'] = $providerConfig['card_pocket_id'];
        }

        if ($providerConfig['name'] == ConfigProvider::CODE_MKB_SZEP) {
            $cardPocketId = $providerConfig['card_pocket_id'];

            // The Home renovation pocket ('4444') has been phased out; redirect
            // this legacy config value to the Main account ('1111').
            if ((string)$cardPocketId === '4444') {
                $cardPocketId = '1111';
            }

            $request
                ->setMkbSzepCafeteriaId($cardPocketId)
                ->setGatewayPaymentPage(true);
        }

        if ($providerConfig['name'] == ConfigProvider::CODE_OTP_SZEP) {
            $request->setOtpCardPocketId($providerConfig['card_pocket_id']);
        }

        if ($providerConfig['name'] == ConfigProvider::CODE_SAFERPAY) {
            if (isset($providerConfig['payment_methods']) && strlen($providerConfig['payment_methods'])) {
                $extraData['SaferpayPaymentMethods'] = explode(',', $providerConfig['payment_methods']);
            }

            if (isset($providerConfig['wallets']) && strlen($providerConfig['wallets'])) {
                $extraData['SaferpayWallets'] = explode(',', $providerConfig['wallets']);
            }
        }

        if ($providerConfig['name'] == ConfigProvider::CODE_GOPAY) {
            if (isset($providerConfig['allowed_payment_methods']) && strlen($providerConfig['allowed_payment_methods'])) {
                $extraData['PaymentMethod'] = explode(',', $providerConfig['allowed_payment_methods']);
            }

            if (isset($providerConfig['default_payment_method']) && strlen($providerConfig['default_payment_method'])) {
                $extraData['DefaultPaymentMethod'] = $providerConfig['default_payment_method'];
            }
        }

        if ($providerConfig['name'] == ConfigProvider::CODE_WIRECARD) {
            if (isset($providerConfig['payment_type']) && strlen($providerConfig['payment_type'])) {
                $extraData['QpayPaymentType'] = $providerConfig['payment_type'];
            }
        }

        if ($providerConfig['name'] == ConfigProvider::CODE_BBARUHITEL) {
            if (isset($providerConfig['offer_id']) && !empty(trim($providerConfig['offer_id']))) {
                $extraData['offerId'] = trim($providerConfig['offer_id']);
            }

            if (isset($providerConfig['term']) && !empty(trim($providerConfig['term']))) {
                $extraData['term'] = trim($providerConfig['term']);
            }
        }

        if ($providerConfig['name'] == ConfigProvider::CODE_OTPARUHITEL) {
            if (isset($providerConfig['construction_group']) && !empty(trim($providerConfig['construction_group']))) {
                $extraData['ConstructionGroup'] = trim($providerConfig['construction_group']);
            }
        }

        if ($this->helper->isOneClickProvider($providerConfig['name']) && $this->customerAcceptCardRegistration()) {
            if (isset($providerConfig['card_registration_mode']) && strlen($providerConfig['card_registration_mode'])) {
                // If the provider is not PSD2 CIT proceed as before
                if (!isset(ConfigProvider::PSD2_CIT_PROVIDERS_CONFIG_MAPPING[$providerConfig['name']])) {
                    $request->setOneClickPayment(true);

                    if ($providerConfig['card_registration_mode'] == '1') {
                        $request->setOneClickForcedRegistration(true);
                    }
                } else {
                    // For specified providers and logged in customer we create the PSD2 compliant CIT payment
                    if ($order->getCustomerId()) {
                        $isCardRegistration = true;
                        if ($providerConfig['card_registration_mode'] == '2') {
                            // To determine if the customer already has a saved card registration we have to query the Payment Gateway GetPaymentRegistrations endpoint
                            $paymentRegistrations = $this->helper->getPaymentRegistrations(
                                $this->getPaymentRegistrationsRequest(
                                    $providerConfig['provider_code'],
                                    (int)$order->getCustomerId(),
                                    PaymentGateway::PAYMENT_REGISTRATION_TYPE_CUSTOMER_INITIATED
                                )
                            );
                            if (
                                $paymentRegistrations->ResultCode == PaymentGateway::RESULT_CODE_SUCCESS
                                && !empty($paymentRegistrations->Data['CIT'])
                                && is_array($paymentRegistrations->Data['CIT'])
                            ) {
                                $isCardRegistration = false;
                            }
                        }

                        if ($isCardRegistration) {
                            $request->setPaymentRegistration(true);
                            $request->setPaymentRegistrationType(PaymentGateway::PAYMENT_REGISTRATION_TYPE_CUSTOMER_INITIATED);
                        } else {
                            $request->setPaymentRegistration(false);
                            $request->setOneClickPayment(true);
                        }
                    }
                }
            }
        }

        if (!empty($extraData)) {
            $request->setExtra($extraData);
        }

        $request->setInfo($this->getInfo($order));

        return $request;
    }


    /**
     * @param string $providerName
     * @param int|null $userId
     * @param string|null $paymentRegistrationType
     * @return GetPaymentRegistrationsRequest
     * @throws PaymentGateway\Exception
     */
    protected function getPaymentRegistrationsRequest($providerName, $userId, $paymentRegistrationType = null)
    {
        return (new GetPaymentRegistrationsRequest())->setProviderName($providerName)->setUserId((string)$userId)->setPaymentRegistrationType($paymentRegistrationType);
    }

    /**
     * @param OrderAdapterInterface $order
     * @return Info
     */
    protected function getInfo(OrderAdapterInterface $order)
    {
        $info = new Info();

        $shippingAddress = new PaymentGateway\Data\Info\Order\InfoOrderShippingData();
        $magentoShipping = $order->getShippingAddress();
        if ($magentoShipping !== null) {
            $shippingAddress
                ->setLastName(mb_substr($magentoShipping->getLastname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setFirstName(mb_substr($magentoShipping->getFirstname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setEmail(mb_substr($magentoShipping->getEmail() ?? '', 0, self::MAX_EMAIL_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setPhone(mb_substr($magentoShipping->getTelephone() ?? '', 0, self::MAX_PHONE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setPostalCode(mb_substr($magentoShipping->getPostcode() ?? '', 0, self::MAX_POSTAL_CODE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setCity(mb_substr($magentoShipping->getCity() ?? '', 0, self::MAX_CITY_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setLine1(mb_substr($magentoShipping->getStreetLine1() ?? '', 0, self::MAX_ADDRESS_LINE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setLine2(mb_substr($magentoShipping->getStreetLine2() ?? '', 0, self::MAX_ADDRESS_LINE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setCountry(mb_substr($magentoShipping->getRegionCode() ?? '', 0, self::MAX_COUNTRY_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setCountryCode2(mb_substr($magentoShipping->getCountryId() ?? '', 0, self::MAX_COUNTRY_CODE_2_LENGTH, self::MB_DEFAULT_ENCODING));
            $info->setObject($shippingAddress);
        }

        $billingAddress = new PaymentGateway\Data\Info\Order\InfoOrderBillingData();
        $magentoBilling = $order->getBillingAddress();
        if ($magentoBilling !== null) {
            $billingAddress
                ->setLastName(mb_substr($magentoBilling->getLastname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setFirstName(mb_substr($magentoBilling->getFirstname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setEmail(mb_substr($magentoBilling->getEmail() ?? '', 0, self::MAX_EMAIL_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setPhone(mb_substr($magentoBilling->getTelephone() ?? '', 0, self::MAX_PHONE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setPostalCode(mb_substr($magentoBilling->getPostcode() ?? '', 0, self::MAX_POSTAL_CODE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setCity(mb_substr($magentoBilling->getCity() ?? '', 0, self::MAX_CITY_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setLine1(mb_substr($magentoBilling->getStreetLine1() ?? '', 0, self::MAX_ADDRESS_LINE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setLine2(mb_substr($magentoBilling->getStreetLine2() ?? '', 0, self::MAX_ADDRESS_LINE_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setCountry(mb_substr($magentoBilling->getRegionCode() ?? '', 0, self::MAX_COUNTRY_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setCountryCode2(mb_substr($magentoBilling->getCountryId() ?? '', 0, self::MAX_COUNTRY_CODE_2_LENGTH, self::MB_DEFAULT_ENCODING));
            $info->setObject($billingAddress);
        }

        if ($order->getCustomerId() !== null) {
            $magentoCustomer = $this->customerRepository->getById($order->getCustomerId());

            $general = new PaymentGateway\Data\Info\Customer\InfoCustomerGeneral();
            $general
                ->setLastName(mb_substr($magentoCustomer->getLastname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setFirstName(mb_substr($magentoCustomer->getFirstname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setEmail(mb_substr($magentoCustomer->getEmail() ?? '', 0, self::MAX_EMAIL_LENGTH, self::MB_DEFAULT_ENCODING))
                ->setIp($order->getRemoteIp());
            $info->setObject($general);

            $storeSpecific = new PaymentGateway\Data\Info\Customer\InfoCustomerStoreSpecific();
            $storeSpecific
                ->setUpdateDate(date('Y-m-d', strtotime($magentoCustomer->getUpdatedAt())))
                ->setCreationDate(date('Y-m-d', strtotime($magentoCustomer->getCreatedAt())));
            $info->setObject($storeSpecific);
        } else {
            if ($magentoBilling !== null) {
                $general = new PaymentGateway\Data\Info\Customer\InfoCustomerGeneral();
                $general
                    ->setLastName(mb_substr($magentoBilling->getLastname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                    ->setFirstName(mb_substr($magentoBilling->getFirstname() ?? '', 0, self::MAX_NAME_LENGTH, self::MB_DEFAULT_ENCODING))
                    ->setEmail(mb_substr($magentoBilling->getEmail() ?? '', 0, self::MAX_EMAIL_LENGTH, self::MB_DEFAULT_ENCODING))
                    ->setIp($order->getRemoteIp());
                $info->setObject($general);
            }

            $storeSpecific = new PaymentGateway\Data\Info\Customer\InfoCustomerStoreSpecific();
            $storeSpecific
                ->setAuthenticationMethod('01');
            $info->setObject($storeSpecific);
        }

        return $info;
    }

    /**
     * @return string
     */
    protected function getStoreBaseUrl()
    {
        return $this->storeManager->getStore()
            ->getBaseUrl();
    }

    /**
     * @return string
     */
    protected function getStoreLanguage()
    {
        return strtoupper(strstr($this->scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeManager->getStore()->getId()), '_', true));
    }

    /**
     * @return bool
     */
    protected function customerAcceptCardRegistration()
    {
        $inputParams = $this->inputParamsResolver->resolve();
        foreach ($inputParams as $inputParam) {
            if ($inputParam instanceof \Magento\Quote\Model\Quote\Payment) {
                $paymentData = $inputParam->getData('additional_data');
                if (isset($paymentData['card_registration'])) {
                    return (bool)$paymentData['card_registration'];
                }
            }
        }
        return false;
    }
}
