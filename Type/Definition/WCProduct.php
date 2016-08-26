<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ListOfType;
use GraphQLRelay\Relay;

class WCProduct extends WPInterfaceType {

    const TYPE = 'WC_Product';
    const DEFAULT_TYPE = 'product';

    static $instances;

    static function init() {
        static::$instances = apply_filters('graphql-wp/get_post_types',[
            'product' => new Product,
        ]);
    }

    static function resolveType($obj) {
        if($obj instanceOf \WC_Product){
            return static::$instances[self::DEFAULT_TYPE];
        }
    }

    static function getDescription() {
        return 'A WooCommerce Product';
    }

    static function toProduct(\WC_Post $post) {
        static $products = [];

        return isset($products[$post->ID]) ? $products[$post->ID] : $products[$post->ID] = wc_get_product($post);
    }

    static function getFieldSchema() {
        return [
            'id' => Relay::globalIdField(self::TYPE, function($post){
                $product = static::toProduct($post);
                return $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id;
            }),
            'ID' => [
                'type' => Type::nonNull(Type::string()),
                'resolve' => function($post) {
                    $product = static::toProduct($post);
                    return (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id;
                }
            ],
            'name' => [
                'type' => Type::string(),
                'resolve' => function($post) {
                    $product = static::toProduct($post);
                    return $product->get_title();
                }
            ],
            'price' => [
                'type' => Type::string(),
                'resolve' => function($post) {
                    $product = static::toProduct($post);
                    return $product->get_price();
                }
            ],
            'description' => [
              'type' => Type::string(),
              'resolve' => function($post) {
                  $product = static::toProduct($post);
                  return wpautop( do_shortcode( $product->get_post_data()->post_content ) );
              }
            ],
            'terms' => [
                'type' => new ListOfType(WPTerm::getInstance()),
                'description' => 'Terms ( Categories, Tags etc ) or this product',
                'args' => [
                    'taxonomy' => [
                        'description' => 'The taxonomy for which to retrieve terms. Defaults to cat.',
                        'type' => Type::string(),
                    ]
                ],
                'resolve' => function($post, $args) {

                    $args += ['taxonomy' => 'cat'];
                    extract($args);

                    $res = wp_get_post_terms($post->ID, 'product_'.$taxonomy);

                    return is_wp_error($res) ? [] : $res;
                }
            ],
            'attributes' => [
                'type' => new ListOfType(ProductAttribute::getInstance()),
                'resolve' => function($post) {

                    $product = static::toProduct($post);
                    $attributes = [];

                    if ( $product->is_type( 'variation' ) ) {
                        // Variation attributes.
                        foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
                            $name = str_replace( 'attribute_', '', $attribute_name );

                            // Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
                            if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
                                $attributes[] = [
                                    'id'     => wc_attribute_taxonomy_id_by_name( $name ),
                                    'name'   => static::get_attribute_taxonomy_label( $name ),
                                    'option' => $attribute,
                                ];
                            } else {
                                $attributes[] = [
                                    'id'     => 0,
                                    'name'   => str_replace( 'pa_', '', $name ),
                                    'option' => $attribute,
                                ];
                            }
                        }
                    }
                    /* else {
                        foreach ( $product->get_attributes() as $attribute ) {
                            if ( $attribute['is_taxonomy'] ) {
                                $attributes[] = array(
                                    'id'        => wc_attribute_taxonomy_id_by_name( $attribute['name'] ),
                                    'name'      => static::get_attribute_taxonomy_label( $attribute['name'] ),
                                    'position'  => (int) $attribute['position'],
                                    'visible'   => (bool) $attribute['is_visible'],
                                    'variation' => (bool) $attribute['is_variation'],
                                    'options'   => static::get_attribute_options( $product->id, $attribute ),
                                );
                            } else {
                                $attributes[] = array(
                                    'id'        => 0,
                                    'name'      => str_replace( 'pa_', '', $attribute['name'] ),
                                    'position'  => (int) $attribute['position'],
                                    'visible'   => (bool) $attribute['is_visible'],
                                    'variation' => (bool) $attribute['is_variation'],
                                    'options'   => static::get_attribute_options( $product->id, $attribute ),
                                );
                            }
                        }
                    }*/

                    return $attributes;
                }
            ],
            'variations' => [
                'type' => function() {
                    return new ListOfType(static::getInstance());
                },
                'resolve' => function($post) {

                    $product = static::toProduct($post);

                    if ( $product->is_type( 'variable' ) && $product->has_child() ) {
                        return array_filter(array_map(function($child_id) use ($product) {
                            $variation = $product->get_child( $child_id );
                            return $variation->exists() ? $variation : null;
                        },$product->get_children()));
                    }
                    return [];
                },
            ],
            'images' => [
                'type' => function(){
                    return new ListOfType(Attachment::getInstance());
                },
                'resolve' => function($post) {

                    $product = static::toProduct($post);

                    if ( $product->is_type( 'variation' ) ) {
                        if ( has_post_thumbnail( $product->get_variation_id() ) ) {
                            // Add variation image if set.
                            $attachment_ids[] = get_post_thumbnail_id( $product->get_variation_id() );
                        } elseif ( has_post_thumbnail( $product->id ) ) {
                            // Otherwise use the parent product featured image if set.
                            $attachment_ids[] = get_post_thumbnail_id( $product->id );
                        }
                    } else {
                        // Add featured image.
                        if ( has_post_thumbnail( $product->id ) ) {
                            $attachment_ids[] = get_post_thumbnail_id( $product->id );
                        }
                        // Add gallery images.
                        $attachment_ids = array_merge( $attachment_ids, $product->get_gallery_attachment_ids() );
                    }

                    return get_posts(['post__in'=>$attachment_ids]);
                }
            ],
		];
    }

    protected static function get_attribute_taxonomy_label( $name ) {
		$tax    = get_taxonomy( $name );
		$labels = get_taxonomy_labels( $tax );

		return $labels->singular_name;
	}

    protected static function get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms( $product_id, $attribute['name'], ['fields' => 'names'] );
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return [];
	}
}
