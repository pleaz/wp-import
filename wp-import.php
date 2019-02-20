<?php

require_once dirname(__FILE__).'/wp-load.php';

global $wpdb;

$args = array(
    'post_type' => 'product',
    'posts_per_page' => 4000
);

$loop = new WP_Query( $args );

$debug = true;

while ( $loop->have_posts() ) : $loop->the_post();
    global $product;

    $attributes = [];
    foreach ($product->attributes as $k => $attribute):

        $k = _truncate_post_slug($k, 27);
        if(substr($k, 0, 3) != 'pa_') {

            $options = $attribute->get_options();

            $taxonomies_arr = [];
            $taxonomies_ids_arr = [];
            foreach(wc_get_attribute_taxonomies() as $tax) {
                $taxonomies_arr[] = $tax;
                $taxonomies_ids_arr[] = $tax->attribute_name;
            }
            $id = array_search($k, $taxonomies_ids_arr);

            if($id!==false) {

                if($options) {
                    $term = get_term_by('name', $options[0], 'pa_'.$k);
                    if($term) {
                        $attribute->set_options( [$term->term_id] );
                    } else {
                        $new_term = wp_insert_term(iconv_substr($options[0], 0, 200, 'UTF-8'), 'pa_'.$k, ['slug' => sanitize_title($options[0])]);
                        if($debug && $new_term->errors) { var_dump($new_term->errors); die(); }
                        $attribute->set_options( [$new_term['term_id']] );
                    }
                } else {
                    $attribute->set_options( [] );
                }
                $attribute->set_id( $taxonomies_arr[$id]->attribute_id );
                $attribute->set_name( 'pa_'.$k );

            } else {

                $new_attr_id = wc_create_attribute([
                    'name' => $attribute->get_name(),
                    'slug' => 'pa_'.$k,
                    'type' => 'select',
                    'order_by' => 'menu_order',
                    'has_archives' => true
                ]);
                if($debug && $new_attr_id->errors) { var_dump($new_attr_id->errors); die(); }
                register_taxonomy('pa_'.$k, 'product', ['label' => $attribute->get_name()]);

                if($options) {
                    $new_term = wp_insert_term(iconv_substr($options[0], 0, 200, 'UTF-8'), 'pa_'.$k, ['slug' => sanitize_title($options[0])]);
                    if($debug && $new_term->errors) { var_dump($new_term->errors); die(); }
                    $attribute->set_options( [$new_term['term_id']] );
                } else {
                    $attribute->set_options( [] );
                }

                $attribute->set_id( $new_attr_id );
                $attribute->set_name( 'pa_'.$k );

            }

            $attributes[] = $attribute;

        }

    endforeach;

    if(!empty($attributes)) {
        $product->set_attributes($attributes);
        $poduct_id = $product->save();
        if($debug && $poduct_id->errors) { var_dump($poduct_id->errors); die(); }
    }

endwhile;

wp_reset_query();
