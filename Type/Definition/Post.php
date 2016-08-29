<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

use \Mohiohio\GraphQLWP\Schema;

class Post extends PostType {

    static function getPostType(){
        return 'post';
    }

    static function getDescription() {
        return 'A standard WordPress blog post';
    }
}
