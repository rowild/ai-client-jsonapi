<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017
 * @package Client
 * @subpackage JsonApi
 */


$target = $this->config( 'client/jsonapi/url/target' );
$cntl = $this->config( 'client/jsonapi/url/controller', 'jsonapi' );
$action = $this->config( 'client/jsonapi/url/action', 'index' );
$config = $this->config( 'client/jsonapi/url/config', array() );


$enc = $this->encoder();


$ref = array( 'id', 'resource', 'filter', 'page', 'sort', 'include', 'fields' );
$params = array_intersect_key( $this->param(), array_flip( $ref ) );

if( !isset( $params['id'] ) ) {
	$params['id'] = '';
}


$fields = $this->param( 'fields', array() );

foreach( (array) $fields as $resource => $list ) {
	$fields[$resource] = array_flip( explode( ',', $list ) );
}


$entryFcn = function( \Aimeos\MShop\Order\Item\Iface $item, \Aimeos\MShop\Common\Item\Helper\Form\Iface $form = null ) use ( $fields, $target, $cntl, $action, $config )
{
	$id = $item->getId();
	$type = $item->getResourceType();
	$params = array( 'resource' => $type, 'id' => $id );
	$attributes = $item->toArray();

	if( isset( $fields[$type] ) ) {
		$attributes = array_intersect_key( $attributes, $fields[$type] );
	}

	$entry = array(
		'id' => $id,
		'type' => $type,
		'links' => array(
			'self' => array(
				'href' => $this->url( $target, $cntl, $action, $params, array(), $config ),
				'allow' => array( 'GET' ),
			),
		),
		'attributes' => $attributes,
	);

	if( $form !== null )
	{
		$entry['links']['related']['href'] = $form->getUrl();
		$entry['links']['related']['allow'] = ( $form->getMethod() !== 'REDIRECT' ? $form->getMethod() : 'GET' );
		$entry['links']['related']['meta'] = [];

		foreach( $form->getValues() as $attr ) {
			$entry['links']['related']['meta'][] = $attr->toArray();
		}
	}

	return $entry;
};


?>
{
	"meta": {
		"total": <?php echo $this->get( 'total', 0 ); ?>

	}

	<?php if( isset( $this->item ) ) : ?>

		,"links": {
			"self": {
				"href": "<?php echo $this->url( $target, $cntl, $action, ['resource' => 'order', 'id' => $params['id']], [], $config ); ?>",
				"allow": ["GET"]
			}
		}

	<?php endif; ?>
	<?php if( isset( $this->errors ) ) : ?>

		,"errors": <?php echo json_encode( $this->errors, JSON_PRETTY_PRINT ); ?>

	<?php elseif( isset( $this->items ) ) : ?>

		<?php
			$data = array();
			$items = $this->get( 'items', [] );

			if( is_array( $items ) )
			{
				foreach( $items as $item ) {
					$data[] = $entryFcn( $item );
				}
			}
			else
			{
				$data = $entryFcn( $items );
			}
		 ?>

		,"data": <?php echo json_encode( $data, JSON_PRETTY_PRINT ); ?>

	<?php endif; ?>

}