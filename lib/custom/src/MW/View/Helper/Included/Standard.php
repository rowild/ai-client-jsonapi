<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2019
 * @package MW
 * @subpackage View
 */


namespace Aimeos\MW\View\Helper\Included;


/**
 * View helper class for generating "included" data used by JSON:API
 *
 * @package MW
 * @subpackage View
 */
class Standard extends \Aimeos\MW\View\Helper\Base implements Iface
{
	private $map;


	/**
	 * Returns the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 * @param array $fcn Associative list of resource types as keys and anonymous mapping functions are values
	 * @return array List of entries to include in the JSON:API response
	 */
	public function transform( \Aimeos\MShop\Common\Item\Iface $item, array $fields, array $fcn = [] )
	{
		$this->map = [];

		if( $item instanceof \Aimeos\MShop\Common\Item\Tree\Iface )
		{
			foreach( $item->getChildren() as $catItem )
			{
				if( $catItem->isAvailable() ) {
					$this->map( $catItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\AddressRef\Iface )
		{
			foreach( $item->getAddressItems() as $addrItem ) {
				$this->map( $addrItem, $fields, $fcn );
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
		{
			foreach( $item->getListItems() as $listItem )
			{
				if( ( $refItem = $listItem->getRefItem() ) !== null ) {
					$this->map( $refItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
		{
			foreach( $item->getPropertyItems() as $propItem ) {
				$this->map( $propItem, $fields, $fcn );
			}
		}

		$result = [];

		foreach( $this->map as $list )
		{
			foreach( $list as $entry ) {
				$result[] = $entry;
			}
		}

		return $result;
	}


	/**
	 * Returns the included data for the JSON:API response
	 *
	 * @param \Aimeos\MShop\Common\Item\Iface $item Object to generate the included data for
	 * @param array $fields Associative list of resource types as keys and field names to output as values
	 * @param array $fcn Associative list of resource types as keys and anonymous mapping functions are values
	 * @return array Multi-dimensional array of included data
	 */
	protected function map( \Aimeos\MShop\Common\Item\Iface $item, array $fields, array $fcn = [] )
	{
		$id = $item->getId();
		$type = $item->getResourceType();

		if( isset( $this->map[$type][$id] ) || !$item->isAvailable() ) {
			return;
		}

		$attributes = $item->toArray();

		if( isset( $fields[$type] ) ) {
			$attributes = array_intersect_key( $attributes, $fields[$type] );
		}

		$entry = ['id' => $id, 'type' => $type, 'attributes' => $attributes];

		if( isset( $fcn[$type] ) && $fcn[$type] instanceof \Closure ) {
			$entry = $fcn[$type]( $item, $entry );
		}

		$this->map[$type][$id] = $entry; // first content, avoid infinite loops

		if( $item instanceof \Aimeos\MShop\Common\Item\Tree\Iface )
		{
			foreach( $item->getChildren() as $childItem )
			{
				if( $childItem->isAvailable() )
				{
					$rtype = $childItem->getResourceType();
					$entry['relationships'][$rtype]['data'][] = ['id' => $childItem->getId(), 'type' => $rtype];
					$this->map( $refItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\ListRef\Iface )
		{
			foreach( $item->getListItems() as $listItem )
			{
				if( ( $refItem = $listItem->getRefItem() ) !== null && $refItem->isAvailable() )
				{
					$rtype = $refItem->getResourceType();
					$data = ['id' => $refItem->getId(), 'type' => $rtype, 'attributes' => $listItem->toArray()];
					$entry['relationships'][$rtype]['data'][] = $data;
					$this->map( $refItem, $fields, $fcn );
				}
			}
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface )
		{
			foreach( $item->getPropertyItems() as $propItem )
			{
				if( $propItem->isAvailable() )
				{
					$propId = $propItem->getId();
					$rtype = $propItem->getResourceType();
					$entry['relationships'][$rtype]['data'][] = ['id' => $propId, 'type' => $rtype];
					$this->map( $propItem, $fields, $fcn );
				}
			}
		}

		$this->map[$type][$id] = $entry; // full content
	}
}
