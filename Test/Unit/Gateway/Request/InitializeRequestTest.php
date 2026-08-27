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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Gateway\Request;

use BigFish\PaymentGateway;
use BigFish\PaymentGateway\Request\Init as InitRequest;
use BigFish\PaymentGateway\Transport\Response\Response;
use Bigfishpaymentgateway\Pmgw\Model\ConfigProvider;
use Bigfishpaymentgateway\Pmgw\Gateway\Request\InitializeRequest;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\StoreInterfaceFixture as StoreInterface;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\Model\Transaction;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Psr\Log\LoggerInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Quote\Api\Data\PaymentInterface;
use \Magento\Webapi\Controller\Rest\InputParamsResolver;

class InitializeRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $productMetaDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $moduleListMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentInterfacedMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $inputParamsResolverMock;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var int
     */
    private $orderId;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $date;

    public function setUp()
    {
        $this->configProviderMock = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();

        $this->productMetaDataMock = $this->getMockBuilder(ProductMetadataInterface::class)
            ->getMock();

        $this->moduleListMock = $this->getMockBuilder(ModuleListInterface::class)
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->getMock();

        $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
            ->getMock();

        $this->paymentMethodMock = $this->getMockBuilder(MethodInterface::class)
            ->getMock();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMock();

        $this->orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->getMock();

        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentInterfacedMock = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inputParamsResolverMock = $this->getMockBuilder(InputParamsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionId = md5(rand(1, 100));
        $this->amount = rand(2000, 4000);
        $this->orderId = rand(1, 1000);
        $this->userId = rand(1, 1000);
        $this->date = date('Y-m-d H:i:s');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function invalidPaymentDataObjectTest()
    {
        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $initializeRequest->build([]);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Payment parameter array should be provided
     */
    public function emptyProviderConfigTest()
    {
        $this->setPaymentDataObjectMockGetPayment();
        $this->setPaymentMockGetMethodInstance();

        $code = 'bigfishpaymentgateway_pmgw_test';
        $config = [];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage ERROR: Error message
     */
    public function failedBuildRequestTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'test_provider_code';

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_ERROR,
            'ResultMessage' => 'Error message',
        ]);

        $this->setProductDataMockGetVersion();
        $this->setModuleListMockGetOne();
        $this->setPaymentDataObjectMockGetPayment();
        $this->setPaymentDataObjectMockGetOrder();
        $this->setPaymentMockGetMethodInstance();
        $this->setStoreManagerMockGetStore();
        $this->setStoreMockGetBaseUrl();
        $this->setStoreMockGetLocaleCode();
        $this->setOrderMockGetOrderIncrementId();
        $this->setOrderMockGetGrandTotalAmount();
        $this->setOrderMockGetCurrencyCode();
        $this->setOrderMockGetCustomerId();
        $this->setHelperMockInitializePaymentGatewayTransaction($expectedInitRequest, $response);
        $this->setLoggerMockCritical('ERROR: Error message');

        $code = 'bigfishpaymentgateway_pmgw_test';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_test',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'test_provider_code',
            'response_url' => '/test_response_url',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]);

    }

    /**
     * @test
     */
    public function successfulBuildRequestTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'test_provider_code';

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_test';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_test',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'test_provider_code',
            'response_url' => '/test_response_url',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    /**
     * @test
     */
    public function successfulBuildRequestWithKhbSzepProviderTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'KHBSZEP';
        $expectedInitRequest->responseUrl = 'http://example.com/test_response_url';
        $expectedInitRequest->extra = $this->urlSafeEncode(json_encode(['KhbCardPocketId' => 'test_card_pocket_id']));

        unset($expectedInitRequest->otpCardPocketId);
        unset($expectedInitRequest->oneClickPayment);
        unset($expectedInitRequest->oneClickReferenceId);
        unset($expectedInitRequest->otpCardNumber);
        unset($expectedInitRequest->otpExpiration);
        unset($expectedInitRequest->otpCvc);
        unset($expectedInitRequest->otpConsumerRegistrationId);
        unset($expectedInitRequest->mkbSzepCardNumber);
        unset($expectedInitRequest->mkbSzepCvv);

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_khbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_khbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'KHBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => 'test_card_pocket_id',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    /**
     * @test
     */
    public function successfulBuildRequestWithMkbSzepProviderTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'MKBSZEP';
        $expectedInitRequest->mkbSzepCafeteriaId = '1111';
        $expectedInitRequest->gatewayPaymentPage = true;

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_mkbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_mkbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'MKBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => '1111',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    /**
     * @test
     */
    public function mkbSzepDeprecatedPocketFallsBackToMainAccountTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'MKBSZEP';
        // The deprecated Home renovation pocket ('4444') must be redirected to Main account ('1111').
        $expectedInitRequest->mkbSzepCafeteriaId = '1111';
        $expectedInitRequest->gatewayPaymentPage = true;

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_mkbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_mkbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'MKBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => '4444',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));
    }

    /**
     * @test
     */
    public function mkbSzepUnknownPocketIsNotSilentlyRemappedTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'MKBSZEP';
        $expectedInitRequest->mkbSzepCafeteriaId = '9999';
        $expectedInitRequest->gatewayPaymentPage = true;

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_mkbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_mkbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'MKBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => '9999',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));
    }

    /**
     * @test
     */
    public function successfulBuildRequestWithOtpSzepProviderTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'OTP';
        $expectedInitRequest->otpCardPocketId = 10;

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_otpszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_otpszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'OTP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => 10,
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    /**
     * @test
     */
    public function successfulBuildRequestWithSaferpayProviderTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'Saferpay';
        $expectedInitRequest->extra = $this->urlSafeEncode(json_encode([
            'SaferpayPaymentMethods' => ['foo', 'bar'],
            'SaferpayWallets' => ['bar', 'foo'],
        ]));

        unset($expectedInitRequest->otpCardPocketId);
        unset($expectedInitRequest->oneClickPayment);
        unset($expectedInitRequest->oneClickReferenceId);
        unset($expectedInitRequest->otpCardNumber);
        unset($expectedInitRequest->otpExpiration);
        unset($expectedInitRequest->otpCvc);
        unset($expectedInitRequest->otpConsumerRegistrationId);
        unset($expectedInitRequest->mkbSzepCardNumber);
        unset($expectedInitRequest->mkbSzepCvv);

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_saferpay';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_saferpay',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'Saferpay',
            'response_url' => '/test_response_url',
            'payment_methods' => 'foo,bar',
            'wallets' => 'bar,foo',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    /**
     * @test
     */
    public function successfulBuildRequestWithWirecardProviderTest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->providerName = 'QPAY';
        $expectedInitRequest->extra = $this->urlSafeEncode(json_encode([
            'QpayPaymentType' => 'foo',
        ]));

        unset($expectedInitRequest->otpCardPocketId);
        unset($expectedInitRequest->oneClickPayment);
        unset($expectedInitRequest->oneClickReferenceId);
        unset($expectedInitRequest->otpCardNumber);
        unset($expectedInitRequest->otpExpiration);
        unset($expectedInitRequest->otpCvc);
        unset($expectedInitRequest->otpConsumerRegistrationId);
        unset($expectedInitRequest->mkbSzepCardNumber);
        unset($expectedInitRequest->mkbSzepCvv);

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_wirecard';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_wirecard',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'QPAY',
            'response_url' => '/test_response_url',
            'payment_type' => 'foo',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->inputParamsResolverMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    /**
     * @param InitRequest $expectedInitRequest
     * @param Response $response
     */
    private function setSuccessfulRequestCommon(InitRequest $expectedInitRequest, Response $response)
    {
        $this->setProductDataMockGetVersion();

        $this->setModuleListMockGetOne();

        $this->setDateTimeMockDate();

        $this->setPaymentDataObjectMockGetPayment();

        $this->setPaymentDataObjectMockGetOrder();

        $this->setPaymentMockGetMethodInstance();

        $this->setStoreManagerMockGetStore();

        $this->setStoreMockGetBaseUrl();

        $this->setStoreMockGetLocaleCode();

        $this->setOrderMockGetOrderIncrementId();

        $this->setOrderMockGetGrandTotalAmount();

        $this->setOrderMockGetCurrencyCode();

        $this->setOrderMockGetCustomerId();

        $this->setHelperMockInitializePaymentGatewayTransaction($expectedInitRequest, $response);

        $this->setTransactionMockSetOrderId();

        $this->setTransactionMockSetTransactionId();

        $this->setTransactionMockSetCreatedTime();

        $this->setTransactionMockSetStatus();

        $this->setTransactionMockSave();

        $this->setHelperMockCreateTransaction();

        $this->setHelperMockAddTransactionLog($response);

    }

    /**
     * @return InitRequest
     */
    private function getExpectedInitRequest()
    {
        $expectedRequest = new InitRequest();
        $expectedRequest->responseUrl = 'http://example.com/test_response_url';
        $expectedRequest->language = 'TS';
        $expectedRequest->amount = $this->amount;
        $expectedRequest->currency = 'TES';
        $expectedRequest->orderId = $this->orderId;
        $expectedRequest->userId = $this->userId;
        $expectedRequest->oneClickPayment = false;
        $expectedRequest->moduleName = 'Magento (test_magento_version)';
        $expectedRequest->moduleVersion = 'test_setup_version';
        $expectedRequest->autoCommit = 'true';

        return $expectedRequest;
    }

    /**
     * @param $string
     * @return string
     */
    private function urlSafeEncode($string)
    {
        $data = str_replace(array('+', '/', '='), array('-', '_', '.'), base64_encode($string));
        return $data;
    }

    private function setProductDataMockGetVersion()
    {
        $this->productMetaDataMock->expects(static::once())
            ->method('getVersion')
            ->will($this->returnValue('test_magento_version'));
    }

    private function setModuleListMockGetOne()
    {
        $this->moduleListMock->expects(static::once())
            ->method('getOne')
            ->with('Bigfishpaymentgateway_Pmgw')
            ->will($this->returnValue([
                'setup_version' => 'test_setup_version',
            ]));
    }

    private function setDateTimeMockDate()
    {
        $this->dateTimeMock->expects(static::once())
            ->method('date')
            ->will($this->returnValue($this->date));
    }

    private function setPaymentDataObjectMockGetPayment()
    {
        $this->paymentDataObjectMock->expects(static::once())
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
    }

    private function setPaymentDataObjectMockGetOrder()
    {
        $this->paymentDataObjectMock->expects(static::once())
            ->method('getOrder')
            ->will($this->returnValue($this->orderMock));
    }

    private function setPaymentMockGetMethodInstance()
    {
        $this->paymentMock->expects(static::once())
            ->method('getMethodInstance')
            ->will($this->returnValue($this->paymentMethodMock));
    }

    private function setStoreManagerMockGetStore()
    {
        $this->storeManagerMock->expects(static::any())
            ->method('getStore')
            ->will($this->returnValue($this->storeMock));
    }

    private function setStoreMockGetBaseUrl()
    {
        $this->storeMock->expects(static::once())
            ->method('getBaseUrl')
            ->with('web')
            ->will($this->returnValue('http://example.com'));
    }

    private function setStoreMockGetLocaleCode()
    {
        $this->storeMock->expects(static::once())
            ->method('getLocaleCode')
            ->will($this->returnValue('ts_TS'));
    }

    private function setOrderMockGetOrderIncrementId()
    {
        $this->orderMock->expects(static::any())
            ->method('getOrderIncrementId')
            ->will($this->returnValue($this->orderId));
    }

    private function setOrderMockGetGrandTotalAmount()
    {
        $this->orderMock->expects(static::once())
            ->method('getGrandTotalAmount')
            ->will($this->returnValue($this->amount));
    }

    private function setOrderMockGetCurrencyCode()
    {
        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->will($this->returnValue('TES'));
    }

    private function setOrderMockGetCustomerId()
    {
        $this->orderMock->expects(static::once())
            ->method('getCustomerId')
            ->will($this->returnValue($this->userId));
    }

    private function setTransactionMockSetOrderId()
    {
        $this->transactionMock->expects(static::once())
            ->method('setOrderId')
            ->with($this->orderId)
            ->will($this->returnSelf());
    }

    private function setTransactionMockSetTransactionId()
    {
        $this->transactionMock->expects(static::once())
            ->method('setTransactionId')
            ->with($this->transactionId)
            ->will($this->returnSelf());
    }

    private function setTransactionMockSetCreatedTime()
    {
        $this->transactionMock->expects(static::once())
            ->method('setCreatedTime')
            ->with($this->date)
            ->will($this->returnSelf());
    }

    private function setTransactionMockSetStatus()
    {
        $this->transactionMock->expects(static::once())
            ->method('setStatus')
            ->with(100)
            ->will($this->returnSelf());
    }

    private function setTransactionMockSave()
    {
        $this->transactionMock->expects(static::once())
            ->method('save');
    }

    private function setHelperMockCreateTransaction()
    {
        $this->helperMock->expects(static::once())
            ->method('createTransaction')
            ->will($this->returnValue($this->transactionMock));
    }

    /**
     * @param InitRequest $expectedInitRequest
     * @param Response $response
     */
    private function setHelperMockInitializePaymentGatewayTransaction(InitRequest $expectedInitRequest, Response $response)
    {
        $this->helperMock->expects(static::once())
            ->method('initializePaymentGatewayTransaction')
            ->with($expectedInitRequest)
            ->will($this->returnValue($response));
    }

    /**
     * @param Response $response
     */
    private function setHelperMockAddTransactionLog(Response $response)
    {
        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response);
    }

    /**
     * @param string $code
     */
    private function setPaymentMethodMockGetCode($code)
    {
        $this->paymentMethodMock->expects(static::once())
            ->method('getCode')
            ->will($this->returnValue($code));
    }

    /**
     * @param string $code
     * @param array $config
     */
    private function setConfigProviderMockGetProviderConfig($code, array $config)
    {
        $this->configProviderMock->expects(static::once())
            ->method('getProviderConfig')
            ->with($code)
            ->will($this->returnValue($config));
    }

    /**
     * @param array $data
     * @return Response
     */
    private function createResponse(array $data)
    {
        $response = new Response(json_encode($data));
        return $response;
    }

    /**
     * @param $errorMessage
     */
    private function setLoggerMockCritical($errorMessage)
    {
        $this->loggerMock->expects(static::once())
            ->method('critical')
            ->with($errorMessage);
    }

}
