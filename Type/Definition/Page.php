<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

class Page extends PostType {

    static function getPostType() {
        return 'page';
    }

    static function getDescription() {
        return 'A standard WordPress page';
    }
}
