<?php
namespace Mohiohio\GraphQLWP;

use \GraphQL\Type\Definition\InterfaceType;
use \GraphQL\Type\Definition\ObjectType;
use \GraphQL\Type\Definition\Type;
use \GraphQL\Type\Definition\IDType;
use \GraphQL\Type\Definition\ListOfType;
use \GraphQL\Type\Definition\EnumType;
use \GraphQLRelay\Relay;

use \Mohiohio\GraphQLWP\Type\Definition\MenuItem;

class Schema
{
    static protected $postInterface = null;
    static protected $termInterface = null;
    static protected $query = null;
    static protected $wpQuery = null;
    static protected $postStatus = null;
    static protected $blogInfo = null;
    static protected $postTypes = [];
    static protected $termTypes = [];
    static protected $nodeDefinition = null;

    const TYPE_POST = 'WP_Post';
    const TYPE_TERM = 'WP_Term';
    const DEFAULT_POST_TYPE = 'post';

    static function build() {
        static::init();
        return new \GraphQL\Schema(static::getQuery());
    }

    static function init() {

        $nodeInterface = static::getNodeDefinition()['nodeInterface'];

        static::$postTypes = static::initObjectTypes(
            static::getPostInterfaceSchema(), // base schema,
            static::getPostTypeSchemas(), // post types,
            ['interfaces'=>[static::getPostInterfaceType(), $nodeInterface]]
        );

        static::$termTypes = static::initObjectTypes(
            static::getTermInterfaceSchema(), // base schema,
            static::getTermTypeSchemas(), // term types,
            ['interfaces'=>[static::getTermInterfaceType(), $nodeInterface]]
        );
    }

    static function initObjectTypes($baseSchema, $schemas, $interfaces) {

        return array_reduce( array_keys($schemas), function($objectTypes, $type) use ($baseSchema, $schemas, $interfaces) {
            $objectTypes[$type] = new ObjectType(array_replace_recursive($baseSchema, $schemas[$type], $interfaces));
            return $objectTypes;
        }, []);
    }

    static function getPostTypeSchemas() {
        return apply_filters('graphql-wp/get_post_types',[
            'post' => [
                'name' => 'Post',
                'description' => 'A standard WordPress blog post',
                'fields' => []
            ],
            'page' => [
                'name' => 'Page',
                'description' => 'A standard WordPress page.',
                'fields' => []
            ]
        ]);
    }

    static function getPostInterfaceType() {

        return static::$postInterface ?: static::$postInterface = new InterfaceType(
            static::getPostInterfaceSchema() + [
                'resolveType' => function ($obj) {
                    if(isset(static::$postTypes[$obj->post_type])){
                        return static::$postTypes[$obj->post_type];
                    }
                }
            ]
        );
    }

    static function getPostInterfaceSchema() {

      return apply_filters('graphql-wp/get_post_interface_schema', [
          'name' => self::TYPE_POST,
          'description' => 'The base WordPress post type',
          'fields' => [
              'id' => Relay::globalIdField(self::TYPE_POST, function($post){
                  return $post->ID;
              }),
              'ID' => [
                  'type' => Type::nonNull(Type::string()),
                  'description' => 'The ID of the post',
              ],
              'name' => [
                  'type' => Type::string(),
                  'description' => 'The post\'s slug',
                  'resolve' => function($post) {
                      return $post->post_name;
                  }
              ],
              'title' => [
                  'type' => Type::string(),
                  'description' => 'The title of the post',
                  'resolve' => function($post) {
                      return get_the_title($post);
                  }
              ],
              'content' => [
                  'type' => Type::string(),
                  'description' => 'The full content of the post',
                  'resolve' => function($post) {
                      return apply_filters('the_content', get_post_field('post_content', $post));
                  }
              ],
              'excerpt' => [
                  'type' => Type::string(),
                  'description' => 'User-defined post except',
                  'args' => [
                      'always' => [
                          'type' => Type::boolean(),
                          'desciption' => 'If true will create an excerpt from post content'
                      ]
                  ],
                  'resolve' => function($post, $args) {

                      $excerpt = apply_filters('the_excerpt',get_post_field('post_excerpt', $post));

                      if(empty($excerpt) && !empty($args['always'])) {
                          $excerpt = apply_filters('the_excerpt', wp_trim_words( strip_shortcodes( $post->post_content )));
                      }

                      return $excerpt;
                  }
              ],
              'date' => [
                  'type' => Type::string(),
                  'description' => 'Format: 0000-00-00 00:00:00',
                  'args' => [
                      'format' => ['type' => Type::string()]
                  ],
                  'resolve' => function($post, $args) {
                      return !empty($args['format']) ? date($args['format'],strtotime($post->post_date)) : $post->post_date;
                  }
              ],
              'date_gmt' => [
                  'type' => Type::string(),
                  'description' => 'Format: 0000-00-00 00:00:00',
                  'args' => [
                      'format' => ['type' => Type::string()]
                  ],
                  'resolve' => function($post, $args) {
                      return !empty($args['format']) ? date($args['format'],strtotime($post->post_date_gmt)) : $post->post_date_gmt;
                  }
              ],
              'status' => [
                  'type' => static::getPostStatusType(),
                  'description' => 'Status of the post',
                  'resolve' => function($post) {
                      return $post->post_status;
                  }
              ],
              'parent' => [
                  'type' => function() {
                      return static::getPostInterfaceType();
                  },
                  'description' => 'Parent of this post',
                  'resolve' => function($post) {
                      return $post->post_parent ? get_post($post->post_parent) : null;
                  }
              ],
              'modified' => [
                  'type' => Type::string(),
                  'description' => 'Format: 0000-00-00 00:00:00',
                  'args' => [
                      'format' => ['type' => Type::string()]
                  ],
                  'resolve' => function($post, $args) {
                      return !empty($args['format']) ? date($args['format'],strtotime($post->post_modified)) : $post->post_modified;
                  }
              ],
              'modified_gmt' => [
                  'type' => Type::string(),
                  'description' => 'Format: 0000-00-00 00:00:00',
                  'args' => [
                      'format' => ['type' => Type::string()]
                  ],
                  'resolve' => function($post, $args) {
                      return !empty($args['format']) ? date($args['format'],strtotime($post->post_modified_gmt)) : $post->post_modified_gmt;
                  }
              ],
              'comment_count' => [
                  'type' => Type::int(),
                  'description' => 'Number of comments on post',
                  'resolve' => function($post) {
                      return $post->comment_count;
                  }
              ],
              'menu_order' => [
                  'type' => Type::int(),
                  'resolve' => function($post) {
                      return $post->menu_order;
                  }
              ],
              'permalink' => [
                  'description' => "Retrieve full permalink for current post ",
                  'type' => Type::string(),
                  'resolve' => function($post) {
                      return get_permalink($post);
                  }
              ],
              'terms' => [
                  'type' => new ListOfType(static::getTermInterfaceType()),
                  'description' => 'Terms ( Categories, Tags etc ) or this post',
                  'args' => [
                      'taxonomy' => [
                          'description' => 'The taxonomy for which to retrieve terms. Defaults to post_tag.',
                          'type' => Type::string(),
                      ],
                      'orderby' => [
                          'description' => "Defaults to name",
                          'type' => Type::string(),
                      ],
                      'order' => [
                          'description' => "Defaults to ASC",
                          'type' => Type::string(),
                      ]
                  ],
                  'resolve' => function($post, $args) {

                      $args += [
                          'taxonomy' => null,
                          'orderby'=>'name',
                          'order' => 'ASC',
                      ];
                      extract($args);

                      $res = wp_get_post_terms($post->ID, $taxonomy, ['orderby'=>$orderby,'order'=>$order]);

                      return is_wp_error($res) ? [] : $res;
                  }
              ]
          ],
      ]);
  }

  static function getPostType($type = null) {
      return static::$postTypes[ $type ?: self::DEFAULT_POST_TYPE ];
  }

  static function getPostStatusType() {
      return static::$postStatus ?: static::$postStatus = new EnumType(static::getPostStatusSchema());
  }

  static function getPostStatusSchema() {

      return apply_filters('grapql-wp/get_post_status_schema',[
          'name' => 'PostStatus',
          'description' => 'A valid post status',
          'values' => [
              'publish' => [
                  'value' => 'publish',
                  'description' => 'A published post or page'
              ],
              'pending' => [
                  'value' => 'pending',
                  'description' => 'post is pending review'
              ],
              'draft' => [
                  'value' => 'draft',
                  'description' => 'a post in draft status'
              ],
              'autodraft' => [
                  'name' => 'autodraft',
                  'value' => 'auto-draft',
                  'description' => 'a newly created post, with no content'
              ],
              'future' => [
                  'value' => 'future',
                  'description' => 'a post to publish in the future',
              ],
              'private' => [
                  'value' => 'private',
                  'description' => 'not visible to users who are not logged in'
              ],
              'inherit' => [
                  'value' => 'inherit',
                  'description' => 'a revision.'
              ],
              'trash' => [
                  'value' => 'trash',
                  'description' => 'post is in trashbin'
              ]
          ]
      ]);
  }

  static function getTermInterfaceType() {
      return static::$termInterface ?: static::$termInterface = new InterfaceType(
      static::getTermInterfaceSchema() + [
          'resolveType' => function ($obj) {
              if(isset(static::$termTypes[$obj->taxonomy])){
                  return static::$termTypes[$obj->taxonomy];
              }
          }
      ]);
  }

  static function getTermTypeSchemas() {

      return apply_filters('graphql-wp/get_term_types',[
          'category' => [
              'name' => 'Category',
              'description' => "The \'category\' taxonomy lets you group posts together by sorting them into various categories.",
              'fields' => []
          ],
          'post_tag' => [
              'name' => 'Tag',
              'description' => "The \'post_tag\' taxonomy is similar to categories, but more free form.",
              'fields' => []
          ],
          'post_format' => [
             'name' => 'PostFormat',
             'description' => "The 'post_format' taxonomy was introduced in WordPress 3.1 and it is a piece of meta information that can be used by a theme to customize its presentation of a post"
          ]
      ]);
  }

  static function getTermInterfaceSchema() {

      return apply_filters('grapql-wp/get_term_interface_schema', [
          'name' => self::TYPE_TERM,
          'description' => 'Base class for taxonomies such as Category & Tag',
          'fields' => [
              'id' => Relay::globalIdField(self::TYPE_TERM, function($term) {
                  return $term->term_id;
              }),
              'term_id' => ['type' => Type::string()],
              'name' => ['type' => Type::string()],
              'slug' => ['type' => Type::string()],
              'term_taxonomy_id' => ['type' => Type::string()],
              'taxonomy' => ['type' => Type::string()],
              'description' => ['type' => Type::string()],
              'parent' => [
                  'type' => function(){
                      return static::getTermInterfaceType();
                  }
              ],
              'children' => [
                  'type' => function() {
                      return new ListOfType(static::getTermInterfaceType());
                  },
                  'description' => 'retrieves children of the term',
                  'resolve' => function($term) {
                      return array_map(function($id) use ($term) {
                          return get_term( $id, $term->taxonomy);
                      }, get_term_children($term->term_id,$term->taxonomy));
                  }
              ]
          ]
      ]);
  }

  static function getNodeDefinition() {

      return static::$nodeDefinition ?: static::$nodeDefinition = Relay::nodeDefinitions(
      function($globalID) {

          $idComponents = Relay::fromGlobalId($globalID);

          //\Analog::log(var_export($idComponents,true));

          switch ($idComponents['type']){
              case self::TYPE_POST;
              return get_post($idComponents['id']);
              case self::TYPE_TERM;
              return get_term($idComponents['id']);
              default;
              return null;
          }
      },
      function($object) {
          //\Analog::log('node resolving type for '.var_export($object,true).' looking in '.var_export(array_keys(static::$postTypes),true) , \Analog::DEBUG);

          if ($object instanceOf \WP_Post ) {
              return static::$postTypes[$object->post_type];
          }
          if ($object instanceOf \WP_Term) {
              return static::$termTypes[$object->taxonomy];
          }
      }
  );
}


  function getWPQuery() {
      return static::$wpQuery ?: static::$wpQuery = new ObjectType(static::getWPQuerySchema());
  }

  static function getWPQuerySchema() {
      return [
          'name' => 'WPQuery',
          'description' => 'deals with the intricacies of a post request on a WordPress blog',
          'fields' => [
              'posts' => [
                  'type' => new ListOfType(static::getPostInterfaceType()),
                  'args' => [
                      'posts_per_page' => [
                          'description' => 'number of post to show per page',
                          'type' => Type::int(),
                      ],
                      'paged' => [
                          'description' => 'number of page.',
                          'type' => Type::int(),
                      ],
                      'post_type' => [
                          'description' => "Retrieves posts by Post Types, default value is 'post'.",
                          'type' => new ListOfType(Type::string()),
                      ],
                      'name' => [
                          'description' => "Retrieves post by name",
                          'type' => Type::string(),
                      ],
                      'order' => [
                          'description' => "Designates the ascending or descending order of the 'orderby' parameter. Defaults to 'DESC'. An array can be used for multiple order/orderby sets.",
                          'type' => Type::string()
                      ],
                      'orderby' => [
                          'description' => "Sort retrieved posts by parameter. Defaults to 'date (post_date)'. One or more options can be passed.",
                          'type' => Type::string()
                      ],
                      's' => [
                          'description' => "Show posts based on a keyword search.",
                          'type' => Type::string()
                      ],
                      'cat' => [
                          'description' => "Show in this category id",
                          'type' => Type::int()
                      ],
                      'category_name' => [
                          'description' => "Show in this category slug",
                          'type' => Type::string()
                      ],
                      'tag' => [
                          'description' => "Show in this tag slug",
                          'type' => Type::string()
                      ],
                      'tag_id' => [
                          'description' => "Show in this tag id",
                          'type' => Type::int()
                      ]
                  ],
                  'resolve' => function($root, $args) {
                      return $args ? get_posts($args) : $root->posts;
                  }
              ],
              'menu' => [
                  'type' => new ListOfType( new MenuItem(['name'=>'MenuItem']) ),
                  'args' => [
                      'name' => [
                          'type' => Type::nonNull(Type::string()),
                          'description' => "Menu 'id','name' or 'slug'"
                      ]
                  ],
                  'resolve' => function($root, $args) {
                      return wp_get_nav_menu_items($args['name']) ?: [];
                  }
              ],
              'bloginfo' => [
                  'type' => static::getBlogInfoType(),
                  'resolve' => function($root, $args){
                      return isset($args['filter']) ? $args['filter'] : 'raw';
                  }
              ],
              'home_page' => [
                  'type' => static::getPostInterfaceType(),
                  'resolve' => function(){
                      return get_post(get_option('page_on_front'));
                  }
              ],
              'terms' => [
                  'type' => new ListOfType(static::getTermInterfaceType()),
                  'description' => 'Retrieve the terms in a given taxonomy or list of taxonomies. ',
                  'args' => [
                      'taxonomies' => [
                          'description' => 'Array of Taxonomy names. Overides taxonomy argument',
                          'type' => new ListOfType(Type::string()),
                      ],
                      'taxonomy' => [
                          'description' => 'The taxonomy for which to retrieve terms. Defaults to category',
                          'type' => Type::string(),
                      ],
                      'orderby' => [
                          'description' => "Field(s) to order terms by. Accepts term fields ('name', 'slug', 'term_group', 'term_id', 'id', 'description'), 'count' for term taxonomy count, 'include' to match the 'order' of the include param, or 'none' to skip ORDER BY. Defaults to 'name'",
                          'type' => Type::string()
                      ],
                      'order' => [
                          'description' => "Whether to order terms in ascending or descending order. Accepts 'ASC' (ascending) or 'DESC' (descending). Default 'ASC",
                          'type' => Type::string()
                      ],
                      'hide_empty' => [
                          'description' => "Whether to order terms in ascending or descending order. Accepts 'ASC' (ascending) or 'DESC' (descending). Default 'ASC'",
                          'type' => Type::string()
                      ],
                      'include' => [
                          'description' => "Array of term ids to include. Default empty array",
                          'type' => new ListOfType(Type::int()),
                      ],
                      'exclude' => [
                          'description' => "Array of term ids to exclude. Default empty array",
                          'type' => new ListOfType(Type::int())
                      ],
                      'exclude_tree' => [
                          'description' => "Term ids to exclude along with all of their descendant terms. If include is non-empty, exclude_tree is ignored",
                          'type' => new ListOfType(Type::int())
                      ],
                      'number' => [
                          'description' => "Maximum number of terms to return. Default 0 (all)",
                          'type' => Type::int()
                      ],
                      'offset' => [
                          'description' => "The number by which to offset the terms query.",
                          'type' => Type::int()
                      ],
                      'name' => [
                          'description' => "Array of names to return terms for",
                          'type' => new ListOfType(Type::string())
                      ],
                      'slug' => [
                          'description' => "Array of slugs to return terms for",
                          'type' => new ListOfType(Type::string())
                      ],
                      'hierarchical' => [
                          'description' => "Whether to include terms that have non-empty descendants (even if hide_empty is set to true). Default true",
                          'type' => new ListOfType(Type::boolean())
                      ],
                      'search' => [
                          'description' => "Search criteria to match terms. Will be SQL-formatted with wildcards before and after.",
                          'type' => Type::string()
                      ],
                      'name__like' => [
                          'description' => "Retrieve terms with criteria by which a term is LIKE name__like",
                          'type' => Type::string()
                      ],
                      'description__like' => [
                          'description' => "Retrieve terms where the description is LIKE description__like",
                          'type' => Type::string()
                      ],
                      'pad_counts' => [
                          'description' => "Whether to pad the quantity of a term's children in the quantity of each term's \"count\" object variable. Default false",
                          'type' => Type::boolean()
                      ],
                      'get' => [
                          'description' => "Whether to return terms regardless of ancestry or whether the terms are empty. Accepts 'all' or empty (disabled).",
                          'type' => Type::boolean()
                      ],
                      'child_of' => [
                          'description' => "Term ID to retrieve child terms of. If multiple taxonomies are passed, child_of is ignored. Default 0",
                          'type' => Type::int()
                      ],
                      'parent' => [
                          'description' => "Parent term ID to retrieve direct-child terms of.",
                          'type' => Type::int()
                      ],
                      'childless' => [
                          'description' => "True to limit results to terms that have no children. This parameter has no effect on non-hierarchical taxonomies. Default false.",
                          'type' => Type::boolean()
                      ],
                  ],
                  'resolve' => function($root, $args) {

                      $taxonomies = isset($args['taxonomies'])
                      ? $args['taxonomies']
                      : isset($args['taxonomy']) ? $args['taxonomy'] : 'category';

                      return get_terms($taxonomies, $args);
                  }
              ]
          ]
      ];
  }

  static function getBlogInfoType() {
      return static::$blogInfo ?: static::$blogInfo = new ObjectType(static::getBlogInfoSchema());
  }

  static function getBlogInfoSchema() {

      $type = ['type' => Type::string(), 'resolve' => function($filter, $args, $resolveInfo) {
          return get_bloginfo($resolveInfo->fieldName, isset($args['filter']) ?: $filter );
      }];

      return [
          'name' => 'BlogInfo',
          'description' => 'blog info stuff',
          'fields' => [
              'url' => $type,
              'wpurl' => $type,
              'description' => $type,
              'rdf_url' => $type,
              'rss_url' => $type,
              'atom_url' => $type,
              'comments_atom_url' => $type,
              'comments_rss2_url' => $type,
              'admin_email' => $type,
              'blogname' => $type,
          ]
      ];
  }

  static function getQueryArgsPost() {
      return [
          'ID' => [
              'name' => 'ID',
              'description' => 'id of the post',
              'type' => Type::int()
          ],
          'slug' => [
              'name' => 'slug',
              'description' => 'name of the post',
              'type' => Type::string()
          ],
          'post_type' => [
              'name' => 'post_type',
              'description' => 'type of the post',
              'type' => Type::string()
          ]
      ];
  }

  static function postQueryResolve($root, $args) {
      if(isset($args['ID'])){
          return get_post($args['ID']);
      }

      return get_page_by_path( $args['slug'], \OBJECT, isset($args['post_type']) ? $args['post_type'] : self::DEFAULT_POST_TYPE );
  }

  static function resolvePostMeta($post, $args, $info) {
      return get_post_meta($post->ID, $info->fieldName, true);
  }

  static function getQuery() {
      return static::$query ?: static::$query = new ObjectType(static::getQuerySchema());
  }

  static function getQuerySchema() {

      $schema = apply_filters('graphql-wp/get_query_schema',[
          'name' => 'Query',
          'fields' => [
              'wp_query' => [
                  'type' => static::getWPQuery(),
                  'resolve' => function($root, $args) {
                      global $wp_query;
                      return $wp_query;
                  }
              ],
              'wp_post' => [
                  'type' => static::getPostInterfaceType(),
                  'args' => static::getQueryArgsPost(),
                  'resolve' => [get_called_class(), 'postQueryResolve']
              ],
              'term' => [
                  'type' => static::getTermInterfaceType(),
                  'args' => [
                      'id' => [
                          'type' => Type::string(),
                          'desciption' => 'Term id'
                      ]
                  ],
                  'resolve' => function($root, $args) {
                      return get_term($args['id']);
                  }
              ],
              'node' => static::getNodeDefinition()['nodeField']
          ]
      ]);

      return $schema;
  }
}
