<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;

class Order extends PostType {

    static function getPostType() {
        return 'shop_order'; // TODO could be multiple, see wc_get_order_types();
    }

    static function toOrder($post) {

        static $orders = [];

        if($post instanceof \WC_Abstract_Order) {
            //Already an order leave as is
            return $post;
        }

        return isset($orders[$post->ID]) ? $orders[$post->ID] : $orders[$post->ID] = wc_get_order($post);
    }

    static function getFieldSchema() {
        return [
            'line_items' => [
                'type' => new ListOfType(OrderLineItem::getInstance()),
                'resolve' => function($post) {

                    $order = static::toOrder($post);

                    $orderItems = $order->get_items();

                    return array_map( function($item_id, $item) use ($order) {
                        return static::resolveLineItem($item_id, $item, $order);
                    }, array_keys($orderItems), $orderItems);
                }
            ],
            'total' => [
                'type' => Type::string(),
                'resolve' => function($post) {
                    $order = static::toOrder($post);
                    return $order->get_total();
                }],
        ] + parent::getFieldSchema();
    }

    static function getDescription() {
        return 'A standard WooCommerce shop order';
    }

    // Lifted from  WC_REST_Orders_Controller
    static function resolveLineItem($item_id, $item, $order) {

        $product      = $order->get_product_from_item( $item );
        $product_id   = 0;
        $variation_id = 0;
        $product_sku  = null;

        // Check if the product exists.
        if ( is_object( $product ) ) {
            $product_id   = $product->id;
            $variation_id = $product->variation_id;
            $product_sku  = $product->get_sku();
        }

        $meta = new \WC_Order_Item_Meta( $item, $product );

        $item_meta = array();


        //$hideprefix = 'true' === $request['all_item_meta'] ? null : '_';
        foreach ( $meta->get_formatted('_') as $meta_key => $formatted_meta ) {
            $item_meta[] = array(
                'key'   => $formatted_meta['key'],
                'label' => $formatted_meta['label'],
                'value' => $formatted_meta['value'],
            );
        }

        $line_item = array(
            'id'           => $item_id,
            'name'         => $item['name'],
            'sku'          => $product_sku,
            'product_id'   => (int) $product_id,
            'variation_id' => (int) $variation_id,
            'quantity'     => wc_stock_amount( $item['qty'] ),
            'tax_class'    => ! empty( $item['tax_class'] ) ? $item['tax_class'] : '',
            'price'        => wc_format_decimal( $order->get_item_total( $item, false, false )),
            'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item, false, false )),
            'subtotal_tax' => wc_format_decimal( $item['line_subtotal_tax']),
            'total'        => wc_format_decimal( $order->get_line_total( $item, false, false )),
            'total_tax'    => wc_format_decimal( $item['line_tax']),
            'taxes'        => array(),
            'meta'         => $item_meta,
        );

        $item_line_taxes = maybe_unserialize( $item['line_tax_data'] );
        if ( isset( $item_line_taxes['total'] ) ) {
            $line_tax = array();

            foreach ( $item_line_taxes['total'] as $tax_rate_id => $tax ) {
                $line_tax[ $tax_rate_id ] = array(
                    'id'       => $tax_rate_id,
                    'total'    => $tax,
                    'subtotal' => '',
                );
            }

            foreach ( $item_line_taxes['subtotal'] as $tax_rate_id => $tax ) {
                $line_tax[ $tax_rate_id ]['subtotal'] = $tax;
            }

            $line_item['taxes'] = array_values( $line_tax );
        }

        //\Mohiohio\GraphQLWP\log('line item data', $line_item);

        return $line_item;
    }
}
