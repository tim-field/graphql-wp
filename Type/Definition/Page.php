<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

class Page extends Post {

    function getDescription() {
        return 'A standard WordPress page';
    }
}
