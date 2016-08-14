<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

class Page extends Post {

    static function getDescription() {
        return 'A standard WordPress page';
    }
}
