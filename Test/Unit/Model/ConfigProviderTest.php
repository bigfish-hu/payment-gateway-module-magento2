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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Bigfishpaymentgateway\Pmgw\Model\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    public function setUp()
    {
        parent::setUp();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function emptyScopeConfigTest()
    {
        $this->assertConfig([], []);
    }

    /**
     * @test
     */
    public function noPmgwProviderInScopeConfigTest()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'other_provider' => [
                'foo' => 'bar',
            ],
        ], []);
    }

    /**
     * @test
     */
    public function noActivePmgwProviderInScopeConfigTest()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 0,
            ],
        ], []);
    }

    /**
     * @test
     */
    public function oneActivePmgwProviderInScopeConfigTest()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 1,
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_test',
                'active' => 1,
            ],
        ]);
    }

    /**
     * @test
     */
    public function multipleActivePmgwProviderInScopeConfigTest()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_foo' => [
                'active' => 1,
            ],
            'bigfishpaymentgateway_pmgw_bar' => [
                'active' => 1,
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_foo',
                'active' => 1,
            ],
            [
                'name' => 'bigfishpaymentgateway_pmgw_bar',
                'active' => 1,
            ],
        ]);
    }

    /**
     * @test
     */
    public function multipleActiveAndInactivePmgwProviderInScopeConfigTest()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_foo' => [
                'active' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 0,
            ],
            'bigfishpaymentgateway_pmgw_bar' => [
                'active' => 1,
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_foo',
                'active' => 1,
            ],
            [
                'name' => 'bigfishpaymentgateway_pmgw_bar',
                'active' => 1,
            ],
        ]);
    }

    /**
     * @test
     */
    public function unifyPmgwProviderConfigValuesTest()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_foo' => [
                'active' => 1,
                'foo' => 'bar',
            ],
            'bigfishpaymentgateway_pmgw_bar' => [
                'active' => 1,
                'bar' => 'foo',
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_foo',
                'active' => 1,
                'foo' => 'bar',
                'bar' => null,
            ],
            [
                'name' => 'bigfishpaymentgateway_pmgw_bar',
                'active' => 1,
                'foo' => null,
                'bar' => 'foo',
            ],
        ]);
    }

    /**
     * @test
     */
    public function getProviderConfigWithInvalidCodeTest()
    {
        $this->setScopeConfigMockGetValue([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 1,
            ],
        ]);

        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $this->assertEquals([], $configProvider->getProviderConfig('bigfishpaymentgateway_pmgw_foo'));
    }

    /**
     * @test
     */
    public function getProviderConfigWithValidCodeTest()
    {
        $this->setScopeConfigMockGetValue([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 1,
            ],
        ]);

        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $this->assertEquals([
            'name' => 'bigfishpaymentgateway_pmgw_test',
            'active' => 1,
            'debug' => 1,
        ], $configProvider->getProviderConfig('bigfishpaymentgateway_pmgw_test'));
    }

    /**
     * @test
     */
    public function getKhbCardPocketIdTest()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $result = $configProvider->getKhbCardPocketId();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '', '1', '3'
        ], array_keys($result));
    }

    /**
     * @test
     */
    public function getMkbCardPocketIdTest()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $result = $configProvider->getMkbCardPocketId();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '', '1111', '3333'
        ], array_keys($result));
    }

    /**
     * @test
     */
    public function getOtpCardPocketIdTest()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $result = $configProvider->getOtpCardPocketId();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '', '09', '08'
        ], array_keys($result));
    }

    /**
     * @test
     */
    public function getSaferpayPaymentMethodsTest()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $result = $configProvider->getSaferpayPaymentMethods();

        $this->assertEquals(20, count($result));
    }

    /**
     * @test
     */
    public function getSaferpayWalletsTest()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $result = $configProvider->getSaferpayWallets();

        $this->assertEquals(1, count($result));
    }

    /**
     * @test
     */
    public function getWirecardPaymentTypesTest()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $result = $configProvider->getWirecardPaymentTypes();

        $this->assertEquals(25, count($result));
    }

    /**
     * @test
     */
    public function getCardRegistrationModesTest()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $result = $configProvider->getCardRegistrationModes();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '0', '1', '2'
        ], array_keys($result));
    }

    /**
     * @param array $scopeConfig
     * @param array $expected
     */
    private function assertConfig(array $scopeConfig, array $expected)
    {
        $this->setScopeConfigMockGetValue($scopeConfig);

        $configProvider = new ConfigProvider($this->scopeConfigMock);

        $this->assertEquals([
            'payment' => [
                'bigfishpaymentgateway_pmgw' => [
                    'providers' => $expected,
                ],
            ],
        ], $configProvider->getConfig());
    }

    /**
     * @param array $scopeConfig
     */
    private function setScopeConfigMockGetValue(array $scopeConfig)
    {
        $this->scopeConfigMock->expects(static::any())
            ->method('getValue')
            ->with('payment')
            ->will($this->returnValue($scopeConfig));
    }

}
