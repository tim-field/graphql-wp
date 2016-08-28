<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

class Page extends Post {

    const POST_TYPE = 'page';

    static function getDescription() {
        return 'A standard WordPress page';
    }
}
