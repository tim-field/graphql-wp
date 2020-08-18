<?php

namespace Mohiohio\GraphQLWP\Type\Definition;

class Category extends Tag {

    const TAXONOMY = 'category';

    static function getDescription() {
        return "The \'category\' taxonomy lets you group posts together by sorting them into various categories.";
    }
}
