# graphql-wp
A GraphQL endpoint for WordPress

This is a WordPress Plugin that exposes a GraphQL endpoint at **/graphql**

This is a work in progress / in active development, but already pretty useful.

Uses this excellent [graphql-php](https://github.com/webonyx/graphql-php) library.

##Install
`composer require mohiohio/graphql-wp`

Assuming you have something like this in your composer.json file ( so it knows to install it in your plugin directory )

    "extra" : {
        "wordpress-install-dir": "public/wp",
        "installer-paths": {
            "public/wp-content/plugins/{$name}/": ["type:wordpress-plugin"],
            "public/wp-content/themes/{$name}/": ["type:wordpress-theme"]
        }
    },


##Using

The best way to explore / develop with this is by using a tool such as [ChromeiQL](https://chrome.google.com/webstore/detail/chromeiql/fkkiamalmpiidkljmicmjfbieiclmeij) That will show you the endpoints and arguments that are available.

###wp_query
This is designed to follow WordPress' existing WP Query functions.  So as a rule you can pass the same parameters as your can to [WP Query](https://codex.wordpress.org/Class_Reference/WP_Query)*.

**In reality there are a lot of params you can pass to WP_Query, and I've only implemented the ones that I've needed so far. But adding more is trivial as the arguments are just passed directly to the get_posts function, so its just a matter of defining them in the schema.* 

    {"query":"{ 
    	wp_query { 
    		posts(paged: 1 posts_per_page: 10)  { 
    			title 
    			name 
    			terms (taxonomy:\"category\") { 
    				name 
    				slug 
    			}
    		}
    	}
    }"}

Will give you

    {
      "data": {
        "wp_query": {
          "posts": [
           {
              "title": "Much better than REST",
              "name": "so-easy-yes"
              "terms": [
              {
	              "name": "Example Category ",
	              "slug": "example-category"
	          }
              ]
           } ...

Also available on wp_query menu 

    {"query":
	    "{ wp_query 
		    { menu(name: \"Main Menu\")  { 
			    title 
			    url
			}
		}
	}"}

Will give you

    {
      "data": {
        "wp_query": {
          "menu": [
            {
              "title": "Home",
              "url": "http://graphqlwordpress.dev/"
            }
          ]
        }
      }
    }

###Post

And of course you can get an individual post *( but most of the time you'll probably use wp_query as your main entry point )*

`{"query":"{wp_post(ID:\"1\") { title, content, status }}"}`

###Custom Post Types

This is how I'm adding custom post types ( which have custom fields ) to my client specific plugin.  
 **graphql-wp/get_post_types** is a good hook for this.

Where `$types` is a hash of the schema we are working with, so just add new items into this and you are good to go.

    use \Mohiohio\GraphQLWP\Schema;

    add_filter('graphql-wp/get_post_types', function($types) {
    
        $types[self::TYPE] = [
            'name' => 'Artist',
            'description' => 'A custom post type example',
            'fields' => [
                'website' => [
                    'type' => Type::string(),
                    'resolve' => function($post) {
                        return get_field('website',$post->ID);
                    },
                ],
                'image' => [
                    'type' => new ACFImage([
                        'name'=>'image'
                    ]),
                    'resolve' => function($post) {
                        return get_field('image',$post->ID);
                    },
                ],
                'news' => [
                    'type' => function() {
                        return new ListOfType(Schema::getType('post'));
                    },
                    'resolve' => function($post) {
                        return get_posts([
                            'connected_type' => self::CONNECTION_NEWS,
                            'connected_items' => $post,
                            'nopaging' => true,
                            'suppress_filters' => false
                        ]) ?: [];
                    }
                ],
                'downloads' => [
                    'type' => new ListOfType( new ObjectType([
                        'name' => 'Downloads',
                        'fields' => [
                            'file' => [
                                'type' => new ACFFile(['name'=>'File']),
                                'resolve' => function($fieldset) {
                                    return $fieldset['file'];
                                }
                            ]
                        ]
                    ])),
                    'resolve' => function($post) {
                        return get_field('downloads',$post->ID) ?: [];
                    }
                ])),
                    'resolve' => function($post) {
                        return static::getTourDates($post);
                    }
                ]
            ]
        ];
        return $types;
    }
    
    },10);

