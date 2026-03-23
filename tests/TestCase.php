<?php

namespace Omnipay\Garantibbva\Tests;

use Faker\Factory;
use Omnipay\Garantibbva\Gateway;
use Omnipay\Tests\GatewayTestCase;

class TestCase extends GatewayTestCase
{
	public $faker;

	public $gateway;

	public function setUp(): void
	{
		parent::setUp();

		$this->faker = Factory::create("tr_TR");

		$this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
	}
}
