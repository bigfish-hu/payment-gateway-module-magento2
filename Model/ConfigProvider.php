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
namespace Bigfishpaymentgateway\Pmgw\Model;

use BigFish\PaymentGateway;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'bigfishpaymentgateway_pmgw';

    const CODE_BARION2 = 'bigfishpaymentgateway_pmgw_barion2';
    const CODE_BBARUHITEL = 'bigfishpaymentgateway_pmgw_bbaruhitel';
    const CODE_BORGUN = 'bigfishpaymentgateway_pmgw_borgun';
    const CODE_BORGUN2 = 'bigfishpaymentgateway_pmgw_borgun2';
    const CODE_CIB = 'bigfishpaymentgateway_pmgw_cib';
    const CODE_ESCALION = 'bigfishpaymentgateway_pmgw_escalion';
    const CODE_FHB = 'bigfishpaymentgateway_pmgw_fhb';
    const CODE_GOPAY = 'bigfishpaymentgateway_pmgw_gopay';
    const CODE_GP = 'bigfishpaymentgateway_pmgw_gp';
    const CODE_IPG = 'bigfishpaymentgateway_pmgw_ipg';
    const CODE_KHB = 'bigfishpaymentgateway_pmgw_khb';
    const CODE_KHB_SZEP = 'bigfishpaymentgateway_pmgw_khbszep';
    const CODE_MKB_SZEP = 'bigfishpaymentgateway_pmgw_mkbszep';
    const CODE_OTP = 'bigfishpaymentgateway_pmgw_otp';
    const CODE_OTPARUHITEL = 'bigfishpaymentgateway_pmgw_otparuhitel';
    const CODE_OTPAY_MP = 'bigfishpaymentgateway_pmgw_otpaymp';
    const CODE_OTP_SIMPLE = 'bigfishpaymentgateway_pmgw_otpsimple';
    const CODE_OTP_SIMPLE_RTP = 'bigfishpaymentgateway_pmgw_otpsimplertp';
    const CODE_OTP_SIMPLE_WIRE = 'bigfishpaymentgateway_pmgw_otpsimplewire';
    const CODE_OTP_SZEP = 'bigfishpaymentgateway_pmgw_otpszep';
    const CODE_PAYPAL = 'bigfishpaymentgateway_pmgw_paypal';
    const CODE_PAYPALREST = 'bigfishpaymentgateway_pmgw_paypalrest';
    const CODE_PAYSAFECARD = 'bigfishpaymentgateway_pmgw_paysafecard';
    const CODE_PAYSAFECASH = 'bigfishpaymentgateway_pmgw_paysafecash';
    const CODE_PAYU2 = 'bigfishpaymentgateway_pmgw_payu2';
    const CODE_PAYUREST = 'bigfishpaymentgateway_pmgw_payurest';
    const CODE_RAIFFEISEN_UPC = 'bigfishpaymentgateway_pmgw_raiffeisenupc';
    const CODE_SAFERPAY = 'bigfishpaymentgateway_pmgw_saferpay';
    const CODE_SOFORT = 'bigfishpaymentgateway_pmgw_sofort';
    const CODE_STRIPE = 'bigfishpaymentgateway_pmgw_stripe';
    const CODE_UNICREDIT = 'bigfishpaymentgateway_pmgw_unicredit';
    const CODE_VIRPAY = 'bigfishpaymentgateway_pmgw_virpay';
    const CODE_WIRECARD = 'bigfishpaymentgateway_pmgw_wirecard';
    const CODE_WIRECARDEE = 'bigfishpaymentgateway_pmgw_wirecardee';
    const CODE_GRANIT = 'bigfishpaymentgateway_pmgw_granit';

    const PSD2_CIT_PROVIDERS_CONFIG_MAPPING = [
        self::CODE_GOPAY          => PaymentGateway::PROVIDER_GOPAY,
        self::CODE_KHB            => PaymentGateway::PROVIDER_KHB,
        self::CODE_RAIFFEISEN_UPC => PaymentGateway::PROVIDER_RAIFFEISENUPC,
    ];

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $providers = $this->getProviders($this->scopeConfig->getValue(
            'payment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        ));

        $this->unifyProviderConfig($providers);

        return [
            'payment' => [
                self::CODE => [
                    'providers' => $providers,
                ]
            ]
        ];
    }

    /**
     * @param $code
     * @return array
     */
    public function getProviderConfig($code)
    {
        $config = $this->getConfig();

        foreach ($config['payment'][self::CODE]['providers'] as $providerConfig) {
            if ($providerConfig['name'] === $code) {
                return array_merge($this->getCommonConfig(), $providerConfig);
            }
        }
        return [];
    }

    /**
     * @return array
     */
    protected function getCommonConfig()
    {
        $scopeConfig = $this->scopeConfig->getValue(
            'payment',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
        return (array)$scopeConfig[self::CODE];
    }

    /**
     * @param array $scopeConfig
     * @return array
     */
    protected function getProviders(array $scopeConfig)
    {
        $prefix = self::CODE . '_';

        $params = array_filter(
            $scopeConfig,
            function (array $data, $key) use ($prefix) {
                return (strpos($key, $prefix) === 0 && (int)$data['active']);
            },
            ARRAY_FILTER_USE_BOTH
        );

        $providers = array_map(
            function ($key, array $data) {
                $data['name'] = $key;
                return $data;
            }, array_keys($params), $params);

        return $providers;
    }

    /**
     * @param array $providers
     */
    protected function unifyProviderConfig(array &$providers)
    {
        $keys = $this->collectProviderConfigKeys($providers);

        array_walk($providers, function (array &$provider) use ($keys) {
            $provider = array_merge($keys, $provider);
        });
    }

    /**
     * @param array $providers
     * @return array
     */
    protected function collectProviderConfigKeys(array $providers)
    {
        $keys = [];

        array_walk($providers, function (array $provider) use (&$keys) {
            foreach (array_keys($provider) as $key) {
                if (array_key_exists($key, $keys)) {
                    continue;
                }
                $keys[$key] = null;
            }
        });
        return $keys;
    }

    /**
     * @return array
     */
    public function getKhbCardPocketId()
    {
        return [
            '' => __('Please, select a pocket.'),
            '1' => __('Main account'),
            '3' => __('Active Hungarians'),
        ];
    }

    /**
     * @return array
     */
    public function getMkbCardPocketId()
    {
        return [
            '' => __('Please, select a pocket.'),
            '1111' => __('Main account'),
            '3333' => __('Active Hungarians'),
        ];
    }

    /**
     * @return array
     */
    public function getOtpCardPocketId()
    {
        return [
            '' => __('Please, select a pocket.'),
            '09' => __('Main account'),
            '08' => __('Active Hungarians'),
        ];
    }

    /**
     * PaymentGateway Saferpay Payment Methods
     *
     * @return array
     */
    public function getSaferpayPaymentMethods()
    {
        return [
            [
                'value' => 'AMEX',
                'label' => __('American Express'),
            ],
            [
                'value' => 'DIRECTDEBIT',
                'label' => __('Direct Debit'),
            ],
            [
                'value' => 'INVOICE',
                'label' => __('Invoice'),
            ],
            [
                'value' => 'BONUS',
                'label' => __('Bonus')
            ],
            [
                'value' => 'DINERS',
                'label' => __('Diners')
            ],
            [
                'value' => 'EPRZELEWY',
                'label' => __('ePrzelewy')
            ],
            [
                'value' => 'EPS',
                'label' => __('eps Online-wire')
            ],
            [
                'value' => 'GIROPAY',
                'label' => __('giropay')
            ],
            [
                'value' => 'IDEAL',
                'label' => __('iDEAL')
            ],
            [
                'value' => 'JCB',
                'label' => __('JCB')
            ],
            [
                'value' => 'MAESTRO',
                'label' => __('Maestro')
            ],
            [
                'value' => 'MASTERCARD',
                'label' => __('MasterCard')
            ],
            [
                'value' => 'MYONE',
                'label' => __('My One')
            ],
            [
                'value' => 'PAYPAL',
                'label' => __('PayPal')
            ],
            [
                'value' => 'POSTCARD',
                'label' => __('PostFinance Card')
            ],
            [
                'value' => 'POSTFINANCE',
                'label' => __('PostFinance')
            ],
            [
                'value' => 'SAFERPAYTEST',
                'label' => __('Saferpay test')
            ],
            [
                'value' => 'SOFORT',
                'label' => __('SOFORT Banking')
            ],
            [
                'value' => 'VISA',
                'label' => __('Visa')
            ],
            [
                'value' => 'VPAY',
                'label' => __('V PAY')
            ],
        ];
    }

    /**
     * PaymentGateway Saferpay Wallets
     *
     * @return array
     */
    public function getSaferpayWallets()
    {
        return [
            [
                'value' => 'MASTERPASS',
                'label' => __('MasterPass'),
            ],
        ];
    }

    /**
     * PaymentGateway GoPay Payment Methods
     *
     * @return array
     */
    public function getGopayPaymentMethods()
    {
        return [
            ['value' => 'PAYMENT_CARD',  'label' => __('Payment card')],
            ['value' => 'BANK_ACCOUNT',  'label' => __('Bank account transfer')],
            ['value' => 'GPAY',          'label' => __('Google Pay')],
            ['value' => 'APPLE_PAY',     'label' => __('Apple Pay')],
            ['value' => 'PAYPAL',        'label' => __('PayPal')],
            ['value' => 'MPAYMENT',      'label' => __('mPlatba (mobile payment)')],
            ['value' => 'PRSMS',         'label' => __('Premium SMS')],
            ['value' => 'PAYSAFECARD',   'label' => __('PaySafeCard coupon')],
            ['value' => 'BITCOIN',       'label' => __('Bitcoin wallet')],
            ['value' => 'CLICK_TO_PAY',  'label' => __('Click To Pay VISA/MasterCard')],
            ['value' => 'TWISTO',        'label' => __('Twisto')],
            ['value' => 'SKIPPAY',       'label' => __('Skip Pay')],
        ];
    }

    /**
     * PaymentGateway GoPay default payment method options.
     *
     * Wraps getGopayPaymentMethods with a leading empty "None"
     * entry so the admin select is nullable. An empty value omits the
     * DefaultPaymentMethod key from the Init Extra payload.
     *
     * @return array
     */
    public function getGopayDefaultPaymentMethodOptions()
    {
        return array_merge(
            [['value' => '', 'label' => __('-- None --')]],
            $this->getGopayPaymentMethods()
        );
    }

    /**
     * @return array
     */
    public function getWirecardPaymentTypes()
    {
        return [
            'SELECT' => __('Select it on Wirecard side'),
            'BANCONTACT_MISTERCASH' => __('Bancontact/Mister Cash'),
            'CCARD' => __('Credit Card Maestro SecureCode'),
            'CCARD-MOTO' => __('Credit Card - Mail Order and Telephone Order'),
            'EKONTO' => __('eKonto'),
            'EPAY_BG' => __('ePay.bg'),
            'EPS' => __('eps Online-wire'),
            'GIROPAY' => __('giropay'),
            'IDL' => __('iDEAL'),
            'MONETA' => __('moneta.ru'),
            'MPASS' => __('mpass'),
            'PRZELEWY24' => __('Przelewy24'),
            'PAYPAL' => __('PayPal'),
            'PBX' => __('paybox'),
            'POLI' => __('POLi'),
            'PSC' => __('paysafecard'),
            'QUICK' => __('@Quick'),
            'SEPA-DD' => __('SEPA Direct Debit'),
            'SKRILLDIRECT' => __('Skrill Direct'),
            'SKRILLWALLET' => __('Skrill Digital Wallet'),
            'SOFORTUEBERWEISUNG' => __('SOFORT Banking'),
            'TATRAPAY' => __('TatraPay'),
            'TRUSTLY' => __('Trustly'),
            'TRUSTPAY' => __('TrustPay'),
            'VOUCHER' => __('My Voucher'),
        ];
    }

    /**
     * @return array
     */
    public function getCardRegistrationModes()
    {
        return [
            '0' => __('No'),
            '1' => __('Only card registration'),
            '2' => __('Card registration and One Click Payment'),
        ];
    }
}
