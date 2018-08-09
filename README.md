# graphql-wp
A GraphQL endpoint for WordPress

This is a WordPress Plugin that exposes a GraphQL endpoint at **/graphql**

Uses this excellent [graphql-php](https://github.com/webonyx/graphql-php) library.  

Supports Relay Connections.

## Should I use this ? :thinking:

You are likely to come across extra functionality that you will want to add to this, as long as you are comfortable with PHP that should be easy to do. I've been working less and less with WordPress and PHP of late so if you want to jump in and take over maintaing this library that would be ideal.  I think all the hard stuff is done :smile:

## Install
`composer require mohiohio/graphql-wp`

Assuming you have something like this in your composer.json file ( so it knows to install it in your plugin directory )
```json
    "extra" : {
        "wordpress-install-dir": "public/wp",
        "installer-paths": {
            "public/wp-content/plugins/{$name}/": ["type:wordpress-plugin"],
            "public/wp-content/themes/{$name}/": ["type:wordpress-theme"]
        }
    },
```

If your aren't familar with using composer with WordPress I'd recommend using a setup like [bedrock](https://roots.io/bedrock/). Otherwise you will at the least need to [require autoload.php](https://getcomposer.org/doc/01-basic-usage.md#autoloading) for this to work.


## Using

The best way to explore / develop with this is by using a tool such as [ChromeiQL](https://chrome.google.com/webstore/detail/chromeiql/fkkiamalmpiidkljmicmjfbieiclmeij) That will show you the endpoints and arguments that are available.

![https://raw.githubusercontent.com/balintsera/graphql-wp/fix/no-response/.readme.md/graphiql-query.png](https://raw.githubusercontent.com/balintsera/graphql-wp/fix/no-response/.readme.md/graphiql-query.png)

### wp_query
This is designed to follow WordPress' existing WP Query functions.  So as a rule you can pass the same parameters as your can to [WP Query](https://codex.wordpress.org/Class_Reference/WP_Query)*.

**In reality there are a lot of params you can pass to WP_Query, and I've only implemented the ones that I've needed so far. But adding more is trivial as the arguments are just passed directly to the get_posts function, so its just a matter of defining them in the schema.*

 ```graphql
query example {
  wp_query {
    posts(paged: 1, posts_per_page: 10) {
      title
      name
      terms(taxonomy: "category") {
        name
        slug
      }
    }
  }
}

```

Will give you

```json
    {
      "data": {
        "wp_query": {
          "posts": [
           {
              "title": "Much better than REST",
              "name": "so-easy-yes",
              "terms": [
                  {
	              "name": "Example Category ",
	              "slug": "example-category"
	          }
              ]
           }
```
Terms and menus and terms are also accessible 

```graphql
query example {
  menu(name:"Main Menu") {
    id
    url
  }
}
```

Will give you
```json
    {
      "data": {
        "menu": [
          {
            "title": "Home",
            "url": "http://graphqlwordpress.dev/"
          }
        ]
      }
    }
```
### Post

And of course you can get an individual post 

```graphql
query example {
  wp_post(ID: 9) {
    title
    content
    status
  }
}
```

### Custom Fields

Any meta fields are available like so

```graphql
query example {
  wp_post(ID: 9) {
    title
    foo: meta_value(key: "foo")
    bar: meta_value(key: "bar")
  }
}
```

If you want to define your own resolver / type you can extend the field schema for a post type like so.

```php
// There is a get_{post_type}_schema call available for each post type
add_filter('graphql-wp/get_post_schema', function($schema) {

    $schema['fields'] = function() use ($schema) {
               // Note call to "parent" function here
        return $schema['fields']() + [
            'foo' => [
                'type' => Type::string(),
                'resolve' => function($post) {
                    return get_post_meta($post->ID, 'foo' ,true);
                }
            ],
            'bar' => [
                'type' => Type::string(),
                'resolve' => function($post) {
                    return get_post_meta($post->ID, 'bar' ,true);
                }
            ]
        ];
    };
    return $schema;
});
```

### Custom Post Types

This is how you can add custom post types ( which have custom fields ) to a client specific plugin.
graphql-wp/get_post_types is a good hook for this.
Where `$types` is a hash of the schema we are working with, so just add new items into this and you are good to go.

```php
use GraphQL\Type\Definition\Type;
use Mohiohio\GraphQLWP\Type\Definition\Post;
use Mohiohio\GraphQLWP\Type\Definition\Attachment;

class Foo extends Post {

    static function getDescription() {
        return "A custom post type example, for post type `foo`";
    }

    static function getFieldSchema() {
        return parent::getFieldSchema() + [
            'website' => [
                'type' => Type::string(),
                'resolve' => function($post) {
                    return get_post_meta($post->ID,'website',true);
                },
            ],
            'image' => [
                'type' => Attachment::getInstance(),
                'resolve' => function($post) {
                    $attachment_id = get_post_meta($post->ID,'image',true);
                    return $attachment_id ? get_post($attachment_id) : null;
                },
            ]
        ];
    }
}

add_filter('graphql-wp/schema-types', function($types){
    return array_merge($types, [
        Foo::getInstance()
    ]);
});
```

### In the wild

http://www.page1management.com/

https://www.wokexpress.co.nz/menu
