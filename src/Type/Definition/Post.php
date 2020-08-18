<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

class Post extends PostType
{

    static function getPostType()
    {
        return 'post';
    }

    static function getDescription()
    {
        return 'A standard WordPress blog post';
    }
}
