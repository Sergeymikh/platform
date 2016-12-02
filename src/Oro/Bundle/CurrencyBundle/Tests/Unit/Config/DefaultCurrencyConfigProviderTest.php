<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Config\DefaultCurrencyConfigProvider;

class DefaultCurrencyConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->configManager->method('get')
            ->with('oro_currency.default_currency')
            ->willReturn('USD');
    }

    public function testGetCurrencyList()
    {
        $currencyConfigManager = new DefaultCurrencyConfigProvider($this->configManager);

        $this->assertCount(1, $currencyConfigManager->getCurrencyList());
    }
}
