<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

class WCProduct extends Post {

    function getDescription() {
        return 'A WooCommerce Product';
    }

    function getFieldSchema() {
        return [

        ] + parent::getFieldSchema();
    }
}
