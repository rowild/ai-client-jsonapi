<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 */


namespace Aimeos\MW\View\Helper\Jincluded;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;


	protected function setUp()
	{
		$this->object = new \Aimeos\MW\View\Helper\Jincluded\Standard( new \Aimeos\MW\View\Standard() );
	}


	protected function tearDown()
	{
		unset( $this->object );
	}


	public function testTransformCatalog()
	{
		$manager = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'catalog' );
		$tree = $manager->getTree( $manager->findItem( 'group' )->getId(), ['media', 'text'] );

		$this->assertEquals( 13, count( $this->object->transform( $tree, [] ) ) );
	}


	public function testTransformCustomer()
	{
		$domains = ['customer/address', 'customer/property'];
		$item = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'customer' )->findItem( 'UTC001', $domains );

		$this->assertEquals( 2, count( $this->object->transform( $item, [] ) ) );
	}


	public function testTransformProduct()
	{
		$domains = ['attribute', 'media', 'price', 'product', 'product/property', 'text'];
		$item = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'product' )->findItem( 'CNE', $domains );

		$this->assertGreaterThanOrEqual( 66, count( $this->object->transform( $item, [] ) ) );
	}


	public function testTransformProducts()
	{
		$domains = ['attribute', 'media', 'price', 'product', 'product/property', 'text'];
		$item = \Aimeos\MShop::create( \TestHelperCustom::getContext(), 'product' )->findItem( 'CNE', $domains );

		$this->assertGreaterThanOrEqual( 66, count( $this->object->transform( [$item], [] ) ) );
	}
}